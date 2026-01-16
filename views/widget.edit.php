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

$form = new CWidgetFormView($data);

$form
	->addField(array_key_exists('show', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['show'])
		: null
	)
	->addField(array_key_exists('server_ids', $data['fields'])
		? (new CWidgetFieldCheckBoxListView($data['fields']['server_ids']))->setColumns(2)
		: null
	)
	->addField(array_key_exists('name', $data['fields'])
		? new CWidgetFieldTextBoxView($data['fields']['name'])
		: null
	)
	->addField(array_key_exists('host', $data['fields'])
		? new CWidgetFieldTextBoxView($data['fields']['host'])
		: null
	)
	->addField(array_key_exists('severities', $data['fields'])
		? new CWidgetFieldSeveritiesView($data['fields']['severities'])
		: null
	)
	->addField(array_key_exists('age_state', $data['fields'])
		? new CWidgetFieldCheckBoxView($data['fields']['age_state'])
		: null
	)
	->addField(array_key_exists('age', $data['fields'])
		? new CWidgetFieldIntegerBoxView($data['fields']['age'])
		: null
	)
	->addField(array_key_exists('acknowledgement_status', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['acknowledgement_status'])
		: null
	)
	->addField(array_key_exists('show_suppressed', $data['fields'])
		? new CWidgetFieldCheckBoxView($data['fields']['show_suppressed'])
		: null
	)
	->addField(array_key_exists('evaltype', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['evaltype'])
		: null
	)
	->addField(array_key_exists('tags', $data['fields'])
		? new CWidgetFieldTagsView($data['fields']['tags'])
		: null
	)
	->addField(array_key_exists('show_tags', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['show_tags'])
		: null
	)
	->addField(array_key_exists('tag_name_format', $data['fields'])
		? new CWidgetFieldRadioButtonListView($data['fields']['tag_name_format'])
		: null
	)
	->addField(array_key_exists('time_period', $data['fields'])
		? new CWidgetFieldTimePeriodView($data['fields']['time_period'])
		: null
	)
	->show();
