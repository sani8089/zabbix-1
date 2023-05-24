<?php
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

require_once dirname(__FILE__).'/../include/CLegacyWebTest.php';
require_once dirname(__FILE__).'/../include/CWebTest.php';
require_once dirname(__FILE__).'/traits/TableTrait.php';

define('HOST_WIDGET', ['id' => 'search_hosts_widget', 'title' => 'Hosts']);
define('HOST_GROUP_WIDGET', ['id' => 'search_hostgroup_widget', 'title' => 'Host groups']);
define('TEMPLATE_WIDGET', ['id' => 'search_templates_widget', 'title' => 'Templates']);

class testPageSearch extends CWebTest {

	use TableTrait;

	public static function getSearchData() {
		return [
			[
				[
					'search_string' => 'Non existant host',
					'host_expected_data' => 'No data found.',
					'host_expected_count' => ['count' => 0, 'total' => 0],
					'hgroup_expected_data' => 'No data found.',
					'hgroup_expected_count' => ['count' => 0, 'total' => 0],
					'template_expected_data' => 'No data found.',
					'template_expected_count' => ['count' => 0, 'total' => 0],
				]
			],
			[
				[
					'search_string' => 'ЗАББИКС Сервер',
					'host_expected_data' => [['Host' => 'ЗАББИКС Сервер'], ['IP' => '127.0.0.1'], ['DNS' => '']],
					'host_expected_count' => ['count' => 1, 'total' => 1],
					'hgroup_expected_data' => 'No data found.',
					'hgroup_expected_count' => ['count' => 0, 'total' => 0],
					'template_expected_data' => 'No data found.',
					'template_expected_count' => ['count' => 0, 'total' => 0],
				]
			],
			[
				[
					'search_string' => 'Zabbix servers',
					'host_expected_data' => 'No data found.',
					'host_expected_count' => ['count' => 0, 'total' => 0],
					'hgroup_expected_data' => [['Host group' => 'Zabbix servers']],
					'hgroup_expected_count' => ['count' => 1, 'total' => 1],
					'template_expected_data' => 'No data found.',
					'template_expected_count' => ['count' => 0, 'total' => 0],
				]
			],
			[
				[
					'search_string' => 'Form test template',
					'host_expected_data' => 'No data found.',
					'host_expected_count' => ['count' => 0, 'total' => 0],
					'hgroup_expected_data' => 'No data found.',
					'hgroup_expected_count' => ['count' => 0, 'total' => 0],
					'template_expected_data' => [['Template' => 'Form test template']],
					'template_expected_count' => ['count' => 1, 'total' => 1],
				]
			],
		];
	}

	/**
	 * Search for an existing Host and check the results page.
	 *
	 * @dataProvider getSearchData
	 */
	public function testPageSearch_ResultsPage($data) {

		$this->openSearchResults($data['search_string']);
		$title = $this->query('id:page-title-general')->waitUntilVisible()->one()->getText();
		$this->assertEquals('Search: '.$data['search_string'], $title);

		$this->verifySearchResultWidget(HOST_WIDGET, $data['host_expected_data'] ?? null, $data['host_expected_count'] ?? null);
		$this->verifySearchResultWidget(HOST_GROUP_WIDGET, $data['hgroup_expected_data'] ?? null, $data['hgroup_expected_count'] ?? null);
		$this->verifySearchResultWidget(TEMPLATE_WIDGET, $data['template_expected_data'] ?? null, $data['template_expected_count'] ?? null);
	}

	/**
	 * Test if the global search form is not being submitted with empty search string.
	 */
	public function testPageSearch_FindEmptyString() {
		$this->zbxTestLogin('zabbix.php?action=dashboard.view');

		// Do not search if the search field is empty.
		$this->zbxTestInputTypeWait('search', '');
		$this->zbxTestClickXpath('//button[@class="search-icon"]');
		$this->zbxTestCheckTitle('Dashboard');
		$this->zbxTestCheckHeader('Global view');

		// Do not search if search string consists only of whitespace characters.
		$this->zbxTestInputTypeWait('search', '   ');
		$this->zbxTestClickXpath('//button[@class="search-icon"]');
		$this->zbxTestCheckTitle('Dashboard');
		$this->zbxTestCheckHeader('Global view');
	}

	/**
	 * Opens Zabbix Dashboard, searches by search string and opens the page.
	 */
	private function openSearchResults($searchString) {
		$this->page->login()->open('zabbix.php?action=dashboard.view');
		$form = $this->query('class:form-search')->asForm()->one()->waitUntilVisible();
		$form->query('id:search')->one()->fill($searchString);
		$form->submit();
	}

	/**
	 * Asserts that a Search result widget contains the expected values.
	 *
	 * @param $widgetParams			array of witget parameters
	 * @param $expectedTableData	expected table data as an array or a string
	 * @param $expectedCount		expected count and total at the footer
	 */
	private function verifySearchResultWidget($widgetParams, $expectedTableData, $expectedCount){
		$this->assertEquals($widgetParams['title'],
			$this->query('xpath://*[@id="'.$widgetParams['id'].'"]//h4')->one()->getText());
		if ($expectedTableData) {
			if(is_array($expectedTableData)){
				$this->assertTableHasData($expectedTableData,'xpath://div[@id="'.$widgetParams['id'].'"]//table');
			}else{
				$tableText = $this->query('xpath://*[@id="'.$widgetParams['id'].'"]//td')->one()->getText();
				$this->assertEquals($expectedTableData, $tableText);
			}
		}
		if ($expectedCount !== null) {
			$footerText = $this->query('xpath://*[@id="'.$widgetParams['id'].'"]//ul[@class="dashbrd-widget-foot"]//li')->one()->getText();
			$this->assertEquals('Displaying '.$expectedCount['count'].' of '.$expectedCount['total'].' found', $footerText);
		}
	}
}
