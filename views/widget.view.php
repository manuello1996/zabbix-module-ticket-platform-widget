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

$table_header = [
	_('Time'),
	_('Severity'),
	_('Status'),
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
	->setHeader($table_header);

$tags_by_event = [];
if ($show_tags != SHOW_TAGS_NONE) {
	$tags_by_event = makeTags($data['problems'], true, 'eventid', $show_tags, $fields['tags'], null,
		(int) $fields['tag_name_format']
	);
}

foreach ($data['problems'] as $problem) {
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
				->setArgument('show', $fields['show'])
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
			->setArgument('show', $fields['show'])
			->setArgument('server_ids[]', $problem['server_id'])
			->setArgument('name', $problem['name'])
			->setArgument('filter_set', 1)
			->getUrl();
		$trigger_popup = 'javascript:ticketPlatformTriggerPopUp("'.$problem['objectid'].'","'
			.$problem['server_id'].'");';

		$problem_cell = (new CLinkAction($problem['name']))
			->addClass(ZBX_STYLE_WORDBREAK)
			->setMenuPopup([
				'type' => 'submenu',
				'data' => [
					'submenu' => [
						'main_section' => [
							'items' => [
								$problems_url => _('Problems'),
								$trigger_popup => _('Trigger')
							]
						]
					]
				]
			]);
	}

	$row = [
		$time_text,
		CSeverityHelper::makeSeverityCell($problem['severity']),
		$status,
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
