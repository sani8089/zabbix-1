<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2024 Zabbix SIA
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


/**
 * @var CView $this
 * @var array $data
 */

(new CHtmlPage())
	->setTitle(_('Availability report graph'))
	->setDocUrl(CDocHelper::getUrl(CDocHelper::REPORTS_AVAILABILITYREPORT))
	->setNavigation((new CList())->addItem(new CBreadcrumbs([
		(new CSpan())->addItem(new CLink(_('Availability report'),
			(new CUrl('zabbix.php'))->setArgument('action', 'availabilityreport.list')
		)),
		(new CSpan())
			->addItem($data['host']['name'])
			->addClass('wide'),
		(new CSpan())
			->addItem($data['trigger']['description'])
			->addClass('wide')
	])))
	->addItem((new CTableInfo())
		->addRow(new CImg('chart4.php?triggerid='.$data['trigger']['triggerid']))
	)
	->show();
