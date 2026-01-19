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


/**
 * @var CView $this
 * @var array $data
 */

$widget = new CWidgetView($data);

if ($data['errors']) {
	$widget->addItem(
		(new CTableInfo())->setNoDataMessage(implode("\n", $data['errors']))
	);
	$widget->show();
	return;
}

$fields = $data['fields'];
$show_tags = (int) $fields['show_tags'];
$filter = $data['filter'] ?? [
	'show' => $fields['show'],
	'server_ids' => [],
	'name' => '',
	'host' => '',
	'severities' => [],
	'age_state' => 0,
	'age' => 0,
	'acknowledgement_status' => ZBX_ACK_STATUS_ALL,
	'show_suppressed' => 0,
	'evaltype' => TAG_EVAL_TYPE_AND_OR,
	'tags' => $fields['tags'] ?? [],
	'show_tags' => $fields['show_tags'],
	'tag_name_format' => $fields['tag_name_format'],
	'time_period' => ['from' => '', 'to' => ''],
	'sort' => 'clock',
	'sortorder' => ZBX_SORT_DOWN,
	'page' => 1
];

$sort = $filter['sort'] ?? 'clock';
$sortorder = $filter['sortorder'] ?? ZBX_SORT_DOWN;
$page = (int) ($filter['page'] ?? 1);
if ($page < 1) {
	$page = 1;
}

$problems = $data['problems'];

if ($problems) {
	usort($problems, function ($a, $b) use ($sort, $sortorder) {
		switch ($sort) {
			case 'host':
				$left = implode(', ', array_map(function ($host) {
					return $host['name'];
				}, $a['hosts']));
				$right = implode(', ', array_map(function ($host) {
					return $host['name'];
				}, $b['hosts']));
				break;
			case 'severity':
				$left = $a['severity'];
				$right = $b['severity'];
				break;
			case 'name':
				$left = $a['name'];
				$right = $b['name'];
				break;
			case 'server':
				$left = $a['server_name'];
				$right = $b['server_name'];
				break;
			case 'clock':
			default:
				$left = $a['clock'];
				$right = $b['clock'];
				break;
		}

		if ($left == $right) {
			return 0;
		}

		$result = ($left < $right) ? -1 : 1;
		return $sortorder == ZBX_SORT_DOWN ? -$result : $result;
	});
}

$filter_url = (new CUrl('zabbix.php'))
	->setArgument('action', 'ticket.platform')
	->setArgument('show', $filter['show'])
	->setArgument('name', $filter['name'])
	->setArgument('host', $filter['host'])
	->setArgument('sort', $sort)
	->setArgument('sortorder', $sortorder);

foreach ($filter['server_ids'] as $server_id) {
	$filter_url->setArgument('server_ids[]', $server_id);
}
foreach ($filter['severities'] as $severity) {
	$filter_url->setArgument('severities[]', $severity);
}
if ($filter['age_state']) {
	$filter_url->setArgument('age_state', $filter['age_state']);
	$filter_url->setArgument('age', $filter['age']);
}
$filter_url->setArgument('acknowledgement_status', $filter['acknowledgement_status']);
$filter_url->setArgument('show_suppressed', $filter['show_suppressed']);
$filter_url->setArgument('show_tags', $filter['show_tags']);
$filter_url->setArgument('tag_name_format', $filter['tag_name_format']);
if ($filter['tags']) {
	$tag_filter = $filter['tags'][0];
	$filter_url->setArgument('tags[0][tag]', $tag_filter['tag']);
	$filter_url->setArgument('tags[0][operator]', $tag_filter['operator']);
	$filter_url->setArgument('tags[0][value]', $tag_filter['value']);
}
if (array_key_exists('time_period', $filter) && is_array($filter['time_period'])) {
	$time_from = $filter['time_period']['from'] ?? '';
	$time_to = $filter['time_period']['to'] ?? '';
	if ($time_from !== '') {
		$filter_url->setArgument('from', $time_from);
	}
	if ($time_to !== '') {
		$filter_url->setArgument('to', $time_to);
	}
}

$table_header = [
	_('Time'),
	_('Severity'),
	_('Status'),
	_('Recovery time'),
	_('Origin server'),
	_('Host'),
	_('Problem'),
	_('Duration'),
	_('Acknowledged'),
	_('Update'),
	_('Actions')
];

if ($show_tags != SHOW_TAGS_NONE) {
	$table_header[] = _('Tags');
}

$table = (new CTableInfo())
	->setHeader($table_header)
	->setPageNavigation(CPagerHelper::paginate($page, $problems, $sortorder, $filter_url));

$tags_by_event = [];
if ($show_tags != SHOW_TAGS_NONE) {
	$tags_by_event = makeTags($problems, true, 'eventid', $show_tags, $fields['tags'], null,
		(int) $fields['tag_name_format']
	);
}

foreach ($problems as $problem) {
	$time_text = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $problem['clock']);
	if (!empty($problem['objectid'])) {
		$time_text = new CLink($time_text,
			(new CUrl('zabbix.php'))
				->setArgument('action', 'ticket.platform.eventdetails')
				->setArgument('server_id', $problem['server_id'])
				->setArgument('eventid', $problem['eventid'])
				->setArgument('triggerid', $problem['objectid'])
		);
	}

	$acknowledged = (new CSpan($problem['acknowledged'] ? _('Yes') : _('No')))->addClass(
		$problem['acknowledged'] ? ZBX_STYLE_GREEN : ZBX_STYLE_RED
	);

	$status = (new CSpan($problem['r_eventid'] ? _('RESOLVED') : _('PROBLEM')))->addClass(
		$problem['r_eventid'] ? ZBX_STYLE_GREEN : ZBX_STYLE_RED
	);

	$recovery_time = '';
	if (!empty($problem['r_eventid']) && !empty($problem['r_clock'])) {
		$recovery_time = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $problem['r_clock']);
	}

	$host_list = '';
	if ($problem['hosts']) {
		$host_links = [];
		foreach ($problem['hosts'] as $host) {
			if ($problem['server_id'] === 'local') {
			$host_links[] = (new CLinkAction($host['name']))
				->setMenuPopup(CMenuPopupHelper::getHost($host['hostid']));
			continue;
		}

		$host_problems_url = (new CUrl('zabbix.php'))
			->setArgument('action', 'ticket.platform')
			->setArgument('show', $filter['show'])
			->setArgument('server_ids[]', $problem['server_id'])
			->setArgument('host', $host['name'])
			->setArgument('filter_set', 1)
			->getUrl();
			$host_popup = 'javascript:ticketPlatformRemoteHostPopUp("'.$host['hostid'].'","'
				.$problem['server_id'].'");';

			$host_links[] = (new CLinkAction($host['name']))
				->setMenuPopup([
					'type' => 'submenu',
					'data' => [
						'submenu' => [
							'view' => [
								'label' => _('View'),
								'items' => [
									$host_problems_url => _('Problems')
								]
							],
							'configuration' => [
								'label' => _('Configuration'),
								'items' => [
									$host_popup => _('Host')
								]
							]
						]
					]
				]);
		}

		$host_items = [];
		foreach ($host_links as $index => $link) {
			if ($index > 0) {
				$host_items[] = ', ';
			}
			$host_items[] = $link;
		}
		$host_list = new CSpan($host_items);
	}

	$update_link = (new CLink(_('Update')))
		->setAttribute('data-eventid', $problem['eventid'])
		->setAttribute('data-serverid', $problem['server_id'])
		->onClick('window.ticketPlatformAcknowledgePopUp({eventids: [this.dataset.eventid], server_id: this.dataset.serverid}, this);');

	$actions_icon = '';
	if (($problem['actions_count'] ?? 0) > 0) {
		$actions_icon = (new CButtonIcon(ZBX_ICON_BULLET_RIGHT_WITH_CONTENT))
			->setAttribute('data-content', $problem['actions_count'])
			->setAttribute('aria-label',
				_xn('%1$s action', '%1$s actions', $problem['actions_count'], 'screen reader', $problem['actions_count'])
			)
			->setAjaxHint([
				'action' => 'ticket.platform.actionlist',
				'data' => [
					'eventid' => $problem['eventid'],
					'server_id' => $problem['server_id']
				]
			], ZBX_STYLE_HINTBOX_WRAP_HORIZONTAL);

		if (!empty($problem['actions_has_failed'])) {
			$actions_icon->addClass(ZBX_STYLE_COLOR_NEGATIVE);
		}
		elseif (!empty($problem['actions_has_uncomplete'])) {
			$actions_icon->addClass(ZBX_STYLE_COLOR_WARNING);
		}
	}

	$problem_cell = $problem['name'];
	if (!empty($problem['objectid'])) {
		$problems_url = (new CUrl('zabbix.php'))
			->setArgument('action', 'ticket.platform')
			->setArgument('show', $filter['show'])
			->setArgument('server_ids[]', $problem['server_id'])
			->setArgument('name', $problem['name'])
			->setArgument('filter_set', 1)
			->getUrl();
		$trigger_popup = 'javascript:ticketPlatformTriggerPopUp("'.$problem['objectid'].'","'
			.$problem['server_id'].'");';

		$problem_link = (new CLinkAction($problem['name']))->addClass(ZBX_STYLE_WORDBREAK);

		if ($problem['server_id'] === 'local') {
			$problem_link->setMenuPopup(CMenuPopupHelper::getTrigger([
				'triggerid' => $problem['objectid'],
				'eventid' => $problem['eventid'],
				'backurl' => $filter_url->getUrl()
			]));
		}
		else {
			$item_links = [];
			foreach ($problem['items'] ?? [] as $item) {
				if (!array_key_exists('itemid', $item)) {
					continue;
				}
				$item_links['javascript:ticketPlatformRemoteItemPopUp("'.$item['itemid'].'","'
					.$problem['server_id'].'");'] = $item['name'] ?? $item['itemid'];
			}
			if (!$item_links) {
				$item_links['javascript:void(0)'] = _('No items');
			}

			$problem_link->setMenuPopup([
				'type' => 'submenu',
				'data' => [
					'submenu' => [
						'view' => [
							'label' => _('View'),
							'items' => [
								$problems_url => _('Problems')
							]
						],
						'configuration' => [
							'label' => _('Configuration'),
							'items' => [
								$trigger_popup => _('Trigger'),
								'items' => [
									'label' => _('Items'),
									'items' => $item_links
								]
							]
						]
					]
				]
			]);
		}

		$problem_cell = $problem_link;
	}

	$row = [
		$time_text,
		CSeverityHelper::makeSeverityCell($problem['severity']),
		$status,
		$recovery_time,
		$problem['server_name'],
		$host_list,
		$problem_cell,
		zbx_date2age($problem['clock']),
		$acknowledged,
		$update_link,
		$actions_icon
	];

	if ($show_tags != SHOW_TAGS_NONE) {
		$row[] = $tags_by_event[$problem['eventid']] ?? '';
	}

	$table->addRow($row);
}

$widget
	->addItem($table)
	->show();
