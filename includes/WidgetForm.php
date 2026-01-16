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


namespace Modules\TicketPlatformWidget\Includes;

use Modules\TicketPlatform\Includes\Config as TicketPlatformConfig;
use Zabbix\Widgets\{
	CWidgetField,
	CWidgetForm
};
use Zabbix\Widgets\Fields\{
	CWidgetFieldCheckBox,
	CWidgetFieldCheckBoxList,
	CWidgetFieldIntegerBox,
	CWidgetFieldRadioButtonList,
	CWidgetFieldSeverities,
	CWidgetFieldTags,
	CWidgetFieldTextBox,
	CWidgetFieldTimePeriod
};

class TicketPlatformServerField extends CWidgetFieldCheckBoxList {

	public function __construct(string $name, ?string $label = null, array $values = []) {
		parent::__construct($name, $label, $values);

		$this
			->setSaveType(ZBX_WIDGET_FIELD_TYPE_STR)
			->setValidationRules([
				'type' => API_STRINGS_UTF8,
				'in' => implode(',', array_keys($values)),
				'uniq' => true
			]);
	}
}

class WidgetForm extends CWidgetForm {

	private bool $show_tags = false;

	protected function normalizeValues(array $values): array {
		$values = parent::normalizeValues($values);

		if (array_key_exists('show_tags', $values)) {
			$this->show_tags = $values['show_tags'] != SHOW_TAGS_NONE;
		}

		return $values;
	}

	public function addFields(): self {
		$server_options = $this->getServerOptions();

		return $this
			->addField(
				(new CWidgetFieldRadioButtonList('show', _('Show'), [
					TRIGGERS_OPTION_RECENT_PROBLEM => _('Recent problems'),
					TRIGGERS_OPTION_IN_PROBLEM => _('Problems'),
					TRIGGERS_OPTION_ALL => _('History')
				]))->setDefault(TRIGGERS_OPTION_RECENT_PROBLEM)
			)
			->addField(
				new TicketPlatformServerField('server_ids', _('Servers'), $server_options)
			)
			->addField(
				new CWidgetFieldTextBox('name', _('Problem'))
			)
			->addField(
				new CWidgetFieldTextBox('host', _('Host'))
			)
			->addField(
				new CWidgetFieldSeverities('severities', _('Severity'))
			)
			->addField(
				new CWidgetFieldCheckBox('age_state', _('Age less than'))
			)
			->addField(
				(new CWidgetFieldIntegerBox('age', _('Age (days)'), 1, 999))->setDefault(14)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('acknowledgement_status', _('Acknowledgement status'), [
					ZBX_ACK_STATUS_ALL => _('All'),
					ZBX_ACK_STATUS_UNACK => _('Unacknowledged'),
					ZBX_ACK_STATUS_ACK => _('Acknowledged')
				]))->setDefault(ZBX_ACK_STATUS_ALL)
			)
			->addField(
				new CWidgetFieldCheckBox('show_suppressed', _('Show suppressed problems'))
			)
			->addField(
				(new CWidgetFieldRadioButtonList('evaltype', _('Problem tags'), [
					TAG_EVAL_TYPE_AND_OR => _('And/Or'),
					TAG_EVAL_TYPE_OR => _('Or')
				]))->setDefault(TAG_EVAL_TYPE_AND_OR)
			)
			->addField(
				new CWidgetFieldTags('tags', _('Tag filter'))
			)
			->addField(
				(new CWidgetFieldRadioButtonList('show_tags', _('Show tags'), [
					SHOW_TAGS_NONE => _('None'),
					SHOW_TAGS_1 => SHOW_TAGS_1,
					SHOW_TAGS_2 => SHOW_TAGS_2,
					SHOW_TAGS_3 => SHOW_TAGS_3
				]))->setDefault(SHOW_TAGS_3)
			)
			->addField(
				(new CWidgetFieldRadioButtonList('tag_name_format', _('Tag name format'), [
					TAG_NAME_FULL => _('Full'),
					TAG_NAME_SHORTENED => _('Shortened'),
					TAG_NAME_NONE => _('None')
				]))
					->setDefault(TAG_NAME_FULL)
					->setFlags($this->show_tags ? 0x00 : CWidgetField::FLAG_DISABLED)
			)
			->addField(
				(new CWidgetFieldTimePeriod('time_period', _('Time period')))
			);
	}

	private function getServerOptions(): array {
		if (!class_exists(TicketPlatformConfig::class)) {
			return [];
		}

		$config = TicketPlatformConfig::get();
		$options = [];

		foreach ($config['servers'] as $server) {
			$options[(string) $server['id']] = $server['name'];
		}

		$local_name = $config['local_server_name'] ?: _('Local server');
		$options['local'] = $local_name;

		return $options;
	}
}
