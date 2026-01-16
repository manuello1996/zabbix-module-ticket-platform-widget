<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2025 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
**/


namespace Modules\TicketPlatformWidget\Actions;

use CControllerDashboardWidgetView;
use CControllerResponseData;
use Modules\TicketPlatform\Includes\Config as TicketPlatformConfig;
use Modules\TicketPlatform\Includes\ProblemHelper;

class WidgetView extends CControllerDashboardWidgetView {

	protected function doAction(): void {
		if (!class_exists(TicketPlatformConfig::class) || !class_exists(ProblemHelper::class)) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'errors' => [_('Ticket Platform module is not available.')],
				'problems' => []
			]));
			return;
		}

		try {
			$config = TicketPlatformConfig::get();
		}
		catch (\Throwable $e) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'errors' => [_('Ticket Platform configuration error:').' '.$e->getMessage()],
				'problems' => []
			]));
			return;
		}

		$servers = $this->addLocalServer($config['servers'], $config['local_server_name'] ?? '');
		$servers = $this->filterServers($servers, $this->fields_values['server_ids']);

		if (!$servers) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'errors' => [_('No servers selected.')],
				'problems' => []
			]));
			return;
		}

		$normalized_filter = $this->normalizeFilter($this->fields_values);

		$filter = [
			'show' => $normalized_filter['show'],
			'severities' => $normalized_filter['severities'],
			'name' => $normalized_filter['name'],
			'host' => $normalized_filter['host'],
			'acknowledged' => $normalized_filter['acknowledged'],
			'show_suppressed' => $normalized_filter['show_suppressed'],
			'recent' => $normalized_filter['recent'],
			'time_from' => $normalized_filter['time_from'],
			'time_till' => $normalized_filter['time_till'],
			'tags' => $normalized_filter['tags'],
			'evaltype' => $normalized_filter['evaltype'],
			'limit' => 50,
			'show_tags' => $normalized_filter['show_tags']
		];

		try {
			[$problems, $errors] = ProblemHelper::fetchProblems($servers, $filter, (int) $config['cache_ttl']);
		}
		catch (\Throwable $e) {
			$this->setResponse(new CControllerResponseData([
				'name' => $this->getInput('name', $this->widget->getDefaultName()),
				'errors' => [_('Ticket Platform data error:').' '.$e->getMessage()],
				'problems' => []
			]));
			return;
		}

		usort($problems, function ($a, $b) {
			return $b['clock'] <=> $a['clock'];
		});

		$this->setResponse(new CControllerResponseData([
			'name' => $this->getInput('name', $this->widget->getDefaultName()),
			'errors' => array_map(function ($error) {
				return $error['server'].': '.$error['error'];
			}, $errors),
			'problems' => $problems,
			'fields' => [
				'show' => $this->fields_values['show'],
				'show_tags' => $this->fields_values['show_tags'],
				'tags' => $normalized_filter['tags'],
				'tag_name_format' => $this->fields_values['tag_name_format']
			]
		]));
	}

	private function addLocalServer(array $servers, string $local_name): array {
		$server_name = $local_name !== '' ? $local_name : _('Local server');

		$servers[] = [
			'id' => 'local',
			'name' => $server_name,
			'api_url' => '',
			'api_token' => '',
			'hostgroup' => '',
			'include_subgroups' => 1,
			'enabled' => 1,
			'is_local' => true
		];

		return $servers;
	}

	private function filterServers(array $servers, array $server_ids): array {
		if (!$server_ids) {
			return $servers;
		}

		$allowed = array_map('strval', $server_ids);
		$filtered = [];

		foreach ($servers as $server) {
			if (in_array((string) $server['id'], $allowed, true)) {
				$filtered[] = $server;
			}
		}

		return $filtered;
	}

	private function normalizeFilter(array $fields): array {
		$time_from = null;
		$time_till = null;
		$recent = null;

		if ($fields['show'] == TRIGGERS_OPTION_ALL && array_key_exists('time_period', $fields)) {
			$time_period = $fields['time_period'];
			$time_from = array_key_exists('from_ts', $time_period) && $time_period['from_ts'] > 0
				? (int) $time_period['from_ts']
				: null;
			$time_till = array_key_exists('to_ts', $time_period) && $time_period['to_ts'] > 0
				? (int) $time_period['to_ts']
				: null;
		}

		if (!empty($fields['age_state'])) {
			$time_from = time() - ((int) $fields['age'] * SEC_PER_DAY);
		}

		if ($fields['show'] == TRIGGERS_OPTION_ALL || $fields['show'] == TRIGGERS_OPTION_RECENT_PROBLEM) {
			$recent = true;
		}
		elseif ($fields['show'] == TRIGGERS_OPTION_IN_PROBLEM) {
			$recent = false;
		}

		$acknowledged = null;
		if ($fields['acknowledgement_status'] == ZBX_ACK_STATUS_ACK) {
			$acknowledged = true;
		}
		elseif ($fields['acknowledgement_status'] == ZBX_ACK_STATUS_UNACK) {
			$acknowledged = false;
		}

		$tags = array_filter($fields['tags'], function (array $tag): bool {
			return array_key_exists('tag', $tag) && $tag['tag'] !== '';
		});

		return [
			'show' => $fields['show'],
			'severities' => $fields['severities'],
			'name' => $fields['name'],
			'host' => $fields['host'],
			'acknowledged' => $acknowledged,
			'show_suppressed' => (bool) $fields['show_suppressed'],
			'recent' => $recent,
			'time_from' => $time_from,
			'time_till' => $time_till,
			'tags' => $tags,
			'evaltype' => $fields['evaltype'],
			'show_tags' => $fields['show_tags'] != SHOW_TAGS_NONE
		];
	}
}
