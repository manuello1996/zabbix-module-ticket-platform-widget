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


class CWidgetTicketPlatform extends CWidget {
	constructor(...args) {
		super(...args);

		if (typeof window.ticketPlatformAcknowledgePopUp !== 'function') {
			window.ticketPlatformAcknowledgePopUp = function (parameters, trigger_element) {
				return PopUp('ticket.platform.ack.edit', parameters, {
					dialogue_class: 'modal-popup-generic',
					trigger_element: trigger_element
				});
			};
		}

		if (typeof window.ticketPlatformTriggerPopUp !== 'function') {
			window.ticketPlatformTriggerPopUp = function (triggerid, serverid, trigger_element) {
				return PopUp('ticket.platform.trigger.popup', {triggerid: triggerid, server_id: serverid}, {
					dialogue_class: 'modal-popup-generic',
					trigger_element: trigger_element
				});
			};
		}

		if (typeof window.ticketPlatformRemoteHostPopUp !== 'function') {
			window.ticketPlatformRemoteHostPopUp = function (hostid, serverid, trigger_element) {
				return PopUp('ticket.platform.host.popup', {hostid: hostid, server_id: serverid}, {
					dialogueid: 'host_edit',
					dialogue_class: 'modal-popup-large',
					trigger_element: trigger_element
				});
			};
		}
	}
}
