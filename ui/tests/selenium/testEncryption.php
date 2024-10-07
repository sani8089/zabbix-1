<?php
/*
** Zabbix
** Copyright (C) 2001-2024 Zabbix SIA
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


require_once dirname(__FILE__).'/../include/CWebTest.php';

/**
 * @backup config, config_autoreg_tls
 *
 * @onBefore prepareHostProxyData
 *
 * @onAfter clearAutoregistrationData
 */
class testEncryption extends CWebTest {
	const UPDATE_SAME_HOST = 'Same host with PSK Encryption';
	const UPDATE_SAME_PROXY = 'Same proxy with PSK Encryption';
	const HOST_NAME = 'Host with PSK Encryption';
	const PROXY_NAME = 'Proxy with PSK Encryption';

	/**
	 * Attach MessageBehavior to the test.
	 *
	 * @return array
	 */
	public function getBehaviors() {
		return [CMessageBehavior::class];
	}

	public function prepareHostProxyData() {
		$groupid = CDataHelper::call('hostgroup.create', [['name' => 'Group for Encryption']])['groupids'][0];

		CDataHelper::call('host.create', [
				[
					'host' => self::HOST_NAME,
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'host_identity',
					'tls_psk' => '41b4d07b27a8efdcc15d4742e03857eba377fe010853a1499b0522df171282cb'
				],
				[
					'host' => self::UPDATE_SAME_HOST,
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'same_host_identity',
					'tls_psk' => '978d5dfe7ddc50489078860a5c9c902632acf8efb0c88c869e3812a4c1a4de04'
				],
				[
					'host' => 'Existing host with PSK',
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'existing_host_identity',
					'tls_psk' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
				],
				[
					'host' => '1 Host for mass update',
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'mass_update_identity',
					'tls_psk' => 'f8f07e658898455778b35108c78ebd7e29dbed87de4a5619447e189dd9300d5e'
				],
				[
					'host' => '2 Host for mass update',
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'mass_update_identity',
					'tls_psk' => 'f8f07e658898455778b35108c78ebd7e29dbed87de4a5619447e189dd9300d5e'
				],
				[
					'host' => '3 Host for mass update',
					'groups' => [['groupid' => $groupid]]
				],
				[
					'host' => '4 Host for mass update',
					'groups' => [['groupid' => $groupid]]
				],
				[
					'host' => '5 Host for mass update',
					'groups' => [['groupid' => $groupid]],
					'tls_connect' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'new_mass_update_identity',
					'tls_psk' => '715faa6ec090613cf417c7b4212ce260bd821831713e9b213c15bc8c80c0b8c5'
				],
				[
					'host' => '6 Host for mass update',
					'groups' => [['groupid' => $groupid]]
				]
			]
		);

		CDataHelper::call('proxy.create', [
				[
					'host' => self::PROXY_NAME,
					'status' => HOST_STATUS_PROXY_ACTIVE,
					'tls_accept' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'proxy_identity',
					'tls_psk' => 'a8a8e9a36a77a9383fa172579ecd2b69d3204b4b6762f0671b6eea029376fe01'
				],
				[
					'host' => self::UPDATE_SAME_PROXY,
					'status' => HOST_STATUS_PROXY_ACTIVE,
					'tls_accept' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'same_proxy_identity',
					'tls_psk' => 'a1b9f8aec63372203063379e5a222adc1970f3a1502a4905d72ead8f607041ab'
				],
				[
					'host' => 'Existing proxy with PSK',
					'status' => HOST_STATUS_PROXY_ACTIVE,
					'tls_accept' => HOST_ENCRYPTION_PSK,
					'tls_psk_identity' => 'existing_proxy_identity',
					'tls_psk' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
				]
			]
		);
	}

	public function prepareAutoregistrationData() {
		CDataHelper::call('autoregistration.update',
			[
				'tls_accept' => 3, // Allow both insecure and TLS with PSK connections.
				'tls_psk_identity' => 'autoregistration_identity',
				'tls_psk' => 'c1be5e2fc488b0934f8f44be69fac48da9037087ea05d7fac05a702e3370370f'
			]
		);
	}

	public static function clearAutoregistrationData() {
		CDataHelper::call('autoregistration.update',
			[
				'tls_accept' => 1 // Allow insecure connections.
			]
		);
	}

	/**
	 * Data provider for creating new and updating existing Autoregistarion PSK configuration.
	 */
	public static function getAutoregistrationData() {
		return [
			// #0 Same identity as host, different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '125d996afb640473665c0a22c0f2746fac7f45f9d78df0d222ca5b2ffa1e93a7'
					],
					'db_query' => 'SELECT * FROM config_autoreg_tls'
				]
			],
			// #1 Same identity as host, same PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #2 Different identity as host, same PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_changed_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #3 Same identity as proxy, different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => 'f1b834e8dc4e1ebc1c16f6d9507e8f72c494a6c59fc5acbd251d906b0822cf45'
					],
					'db_query' => 'SELECT * FROM config_autoreg_tls'
				]
			],
			// #4 Same identity as proxy, same PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #5 New proxy identity, same PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_changed_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #6 Unique identity and PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'unique_autoregistration_identity',
						'PSK' => '53133e56aa72a3b1f1d0d9fa6ecc0a39d5b6d50f73e1806a586a4d2e1d323fb5'
					]
				]
			]
		];
	}

	/**
	 * Test for creating new Autoregistration PSK confing. Configuration resets after every data case.
	 *
	 * @dataProvider getAutoregistrationData
	 *
	 * @onAfter clearAutoregistrationData
	 */
	public function testEncryption_CreateAutoregistration($data) {
		$this->checkEncryption($data);
	}

	public static function getUpdateSameObjectData() {
		return [
			// #0 Same autoregistration identity, different PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'autoregistration_identity',
						'PSK' => '520a493eeb6aec90fdad504c53c1a1da2121fc09fc993c2bda7d9dcb8ab12de8'
					]
				]
			],
			// #1 New autoregistration identity but old PSK.
			[
				[
					'object' => 'configuration',
					'url' => 'zabbix.php?action=autoreg.edit',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_autoregistration_identity',
						'PSK' => '520a493eeb6aec90fdad504c53c1a1da2121fc09fc993c2bda7d9dcb8ab12de8'
					]
				]
			],
			// #2 Same host identity, different PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'same_host_identity',
						'PSK' => '58d9efcfdb41a6b69fc8804f5d78a6bda84afd6aeecf9055ef3167e780edd002'
					]
				]
			],
			// #3 New host identity, same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_host_identity',
						'PSK' => '58d9efcfdb41a6b69fc8804f5d78a6bda84afd6aeecf9055ef3167e780edd002'
					]
				]
			],
			// #4 Same proxy identity, different PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with the same host identity and PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'same_proxy_identity',
						'PSK' => '4ad8fbe21255942603baab3533b033952a1244aa7ec1c6bb137987a832e73e5e'
					]
				]
			],
			// #5 New proxy identity, same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with the same host identity and PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'new_proxy_identity',
						'PSK' => '4ad8fbe21255942603baab3533b033952a1244aa7ec1c6bb137987a832e73e5e'
					]
				]
			]
		];
	}

	/**
	 * Function for testing PSK update in Autoregistration, Hosts and Proxies,
	 * where one field remains as is, and the second field changes.
	 *
	 * @dataProvider getUpdateSameObjectData
	 *
	 * @onBeforeOnce prepareAutoregistrationData
	 */
	public function testEncryption_UpdateSameObject($data) {
		$this->checkEncryption($data, true, true);
	}

	public static function getHostProxyData() {
		return [
			// Create/update Hosts.
			// #0 Existing identity on other host and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same other host identity and different PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '4bb1c1e78832eab6b2f0b4da155705bbbe6fd761ac3b01e88169910ce57348a1'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #1 Same existing identity on other host and the same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same host identity and PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'Connections to host' => 'PSK',
						'PSK identity' => 'existing_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #2 Different identity on other host and the same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with different host identity and same PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'Connections to host' => 'PSK',
						'PSK identity' => 'changed_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #3 Existing identity on proxy and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same proxy identity and different PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => '457dd99d4f11bbcf4a48dd318a509b8a0dadbd254c925ed5e7122203470b7e07'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #4 Same existing identity on proxy and same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same proxy identity and same PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #5 Different identity on other proxy and the same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with different proxy identity and same PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'changed_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #6 Same autoregistration identity and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same autoregistration identity and different PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'Connections to host' => 'PSK',
						'PSK identity' => 'autoregistration_identity',
						'PSK' => 'a45d7f0a6f06f4bd2bea4e4d96b164729316d77dc1a7c787636a2b17010210f8'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #7 Same autoregistration identity and same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with same autoregistration identity and same PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'autoregistration_identity',
						'PSK' => 'c1be5e2fc488b0934f8f44be69fac48da9037087ea05d7fac05a702e3370370f'
					]
				]
			],
			// #8 Different autoregistration identity and same PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with different  autoregistration identity and same PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'changed_autoregistration_identity',
						'PSK' => 'c1be5e2fc488b0934f8f44be69fac48da9037087ea05d7fac05a702e3370370f'
					]
				]
			],
			// #9 Unique host identity and unique PSK.
			[
				[
					'object' => 'host',
					'url' => 'zabbix.php?action=host.list',
					'fields' => [
						'Host name' => 'Host with unique identity and PSK',
						'Groups' => 'Zabbix servers'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'unique_host_identity',
						'PSK' => 'e5a528253adb45b4cabb46e87618e1621cce2a029758e206213345efea1a52a0'
					]
				]
			],
			// Create/update Proxies.
			// #10 Existing identity on other host and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same host identity and different PSK',
						'Proxy mode' => 'Active'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '4bb1c1e78832eab6b2f0b4da155705bbbe6fd761ac3b01e88169910ce57348a1'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #11 Same existing identity on other host and the same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same host identity and same PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'existing_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #12 Different identity on other host and the same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with different host identity and same PSK',
						'Proxy mode' => 'Active'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'changed_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			],
			// #13 Existing identity on proxy and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same other proxy identity and different PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => '457dd99d4f11bbcf4a48dd318a509b8a0dadbd254c925ed5e7122203470b7e07'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #14 Same existing identity on proxy and same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same other proxy identity and same PSK',
						'Proxy mode' => 'Active'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #15 Different identity on other proxy and the same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with different identity and same PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'changed_proxy_identity',
						'PSK' => 'ce1885032dc2808e4ddf462ef60f1672beccb2d0068759921c9b17d034c8478e'
					]
				]
			],
			// #16 Same autoregistration identity and different PSK.
			[
				[
					'expected' => TEST_BAD,
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same autoregistration identity and different PSK',
						'Proxy mode' => 'Active'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'autoregistration_identity',
						'PSK' => 'a45d7f0a6f06f4bd2bea4e4d96b164729316d77dc1a7c787636a2b17010210f8'
					],
					'db_query' => 'SELECT * FROM hosts',
					'message_parameter' => '/1/tls_psk'
				]
			],
			// #17 Same autoregistration identity and same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with same autoregistration identity and same PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'autoregistration_identity',
						'PSK' => 'c1be5e2fc488b0934f8f44be69fac48da9037087ea05d7fac05a702e3370370f'
					]
				]
			],
			// #18 Different autoregistration identity and same PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with different autoregistration identity and same PSK',
						'Proxy mode' => 'Active'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'changed_autoregistration_identity',
						'PSK' => 'c1be5e2fc488b0934f8f44be69fac48da9037087ea05d7fac05a702e3370370f'
					]
				]
			],
			// #19 Unique proxy identity and  PSK.
			[
				[
					'object' => 'proxy',
					'url' => 'zabbix.php?action=proxy.list',
					'fields' => [
						'Proxy name' => 'Proxy with Unique identity and  PSK',
						'Proxy mode' => 'Passive'
					],
					'psk_fields' => [
						'Connections to proxy' => 'PSK',
						'PSK identity' => 'unique_proxy_identity',
						'PSK' => '2a1497ce3748b7cbb311c73cc084cac211298820db48127d942c7f3dea555d1c'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getHostProxyData
	 *
	 * @onBeforeOnce prepareAutoregistrationData
	 */
	public function testEncryption_CreateHostProxy($data) {
		$this->checkEncryption($data);
	}

	/**
	 * @dataProvider getHostProxyData
	 * @dataProvider getAutoregistrationData
	 *
	 * @onBeforeOnce prepareAutoregistrationData
	 */
	public function testEncryption_UpdateAll($data) {
		$this->checkEncryption($data, true);
	}

	public function checkEncryption($data, $update = false, $same = false) {
		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$old_hash = CDBHelper::getHash($data['db_query']);
		}

		$this->page->login()->open($data['url'])->waitUntilReady();
		if ($data['object'] !== 'configuration') {
			if ($update) {
				$object = $same
					? ($data['object'] === 'host' ? self::UPDATE_SAME_HOST : self::UPDATE_SAME_PROXY)
					: ($data['object'] === 'host' ? self::HOST_NAME : self::PROXY_NAME);

				$this->query('link', $object)->waitUntilClickable()->one()->click();
			}
			else {
				$this->query('button', 'Create '.$data['object'])->waitUntilClickable()->one()->click();
			}
		}

		if ($data['object'] === 'host') {
			$dialog = COverlayDialogElement::find()->one()->waitUntilReady();
			$form = $dialog->asForm();
		}
		else {
			$form = $this->query('xpath://main/form')->asForm()->waitUntilVisible()->one();
		}

		if (array_key_exists('fields', $data)) {
			if (!$update) {
				$form->fill($data['fields']);
			}

			// Proxy mode influences 'Encryption' tab fields editability.
			if (CTestArrayHelper::get($data['fields'], 'Proxy mode')) {
				$form->fill(['Proxy mode' => $data['fields']['Proxy mode']]);
			}
		}

		if ($data['object'] !== 'configuration') {
			$form->selectTab('Encryption');
			$form->invalidate();
		}

		if ($update) {
			// Make sure that PSK field is set true and enabled.
			if ($data['object'] === 'proxy') {
				if (CTestArrayHelper::get($data['psk_fields'], 'Connections to proxy') === 'PSK') {
					$form->fill(['Connections to proxy' => 'PSK']);
				}

				if (CTestArrayHelper::get($data['psk_fields'], 'id:tls_in_psk') === true) {
					$form->fill(['id:tls_in_psk' => true]);
				}
			}

			$form->query('button:Change PSK')->waitUntilClickable()->one()->click();
		}

		$form->fill($data['psk_fields']);
		$form->submit();

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$message =  ($data['object'] === 'configuration')
				? 'Cannot update configuration'
				: ('Cannot '.($update ? 'update ' : 'add ').$data['object']);
			$parameter = CTestArrayHelper::get($data, 'message_parameter', '/tls_psk');
			$this->assertMessage(TEST_BAD, $message,
					'Invalid parameter "'.$parameter.'": another tls_psk value is already associated with given tls_psk_identity.'
			);
			$this->assertEquals($old_hash, CDBHelper::getHash($data['db_query']));

			if ($data['object'] === 'host') {
				$dialog->close();
			}
		}
		else {
			if ($data['object'] === 'host') {
				$dialog->waitUntilNotVisible();
			}

			$success_message = ($data['object'] === 'configuration')
				? 'Configuration updated'
				: (ucfirst($data['object']).($update ? ' updated' : ' added'));
			$this->assertMessage(TEST_GOOD, $success_message);
		}
	}

	public static function getMassUpdateData() {
		return [
			// #0 Update two hosts without encryption to existing identity but wrong PSK.
			[
				[
					'expected' => TEST_BAD,
					'hosts' => [
						'3 Host for mass update',
						'4 Host for mass update'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '6f91263b9129cf70ad9705115ce3d67863fccd5b361b05bed0b699f44731743a'
					]
				]
			],
			// #1 Update two hosts with encryption to existing identity but wrong PSK.
			[
				[
					'expected' => TEST_BAD,
					'hosts' => [
						'1 Host for mass update',
						'2 Host for mass update'
					],
					'psk_fields' => [
						'Connections to host' => 'PSK',
						'PSK identity' => 'existing_host_identity',
						'PSK' => '6f91263b9129cf70ad9705115ce3d67863fccd5b361b05bed0b699f44731743a'
					]
				]
			],
			// #2 Update two hosts (one with encryption, one - no encryption) to existing identity but wrong PSK.
			[
				[
					'expected' => TEST_BAD,
					'hosts' => [
						'1 Host for mass update',
						'3 Host for mass update'
					],
					'psk_fields' => [
						'Connections to host' => 'PSK',
						'PSK identity' => 'existing_host_identity',
						'PSK' => '6f91263b9129cf70ad9705115ce3d67863fccd5b361b05bed0b699f44731743a'
					]
				]
			],
			// #3 Update two hosts (one with encryption, one - no encryption) to same identity and same PSK.
			[
				[
					'hosts' => [
						'1 Host for mass update',
						'4 Host for mass update'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'mass_update_identity',
						'PSK' => 'f8f07e658898455778b35108c78ebd7e29dbed87de4a5619447e189dd9300d5e'
					]
				]
			],
			// #4 Update two hosts (one with encryption, one - no encryption) to same identity and new PSK.
			[
				[
					'hosts' => [
						'5 Host for mass update',
						'6 Host for mass update'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_mass_update_identity',
						'PSK' => '8a89ec355beb83d9a4a78db5a9d495139ac70b6bafcc2930b8fed0aee7f13bc4'
					]
				]
			],
			// #5 Update hosts with different encryption to same identity and new PSK.
			[
				[
					'hosts' => [
						'1 Host for mass update',
						'5 Host for mass update',
						'6 Host for mass update'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'new_mass_update_identity',
						'PSK' => 'd7b3d893fba18fbea076ba5e849ae71d8ae17bef88ccd946cf889ffa2ca7c213'
					]
				]
			],
			// #6 Update two hosts with encryption to "No encryption".
			[
				[
					'hosts' => [
						'1 Host for mass update',
						'4 Host for mass update'
					],
					'psk_fields' => [
						'Connections to host' => 'No encryption',
						'id:tls_in_psk' => false
					]
				]
			],
			// #7 Update two hosts without encryption to new existing host identity and PSK.
			[
				[
					'hosts' => [
						'1 Host for mass update',
						'4 Host for mass update'
					],
					'psk_fields' => [
						'id:tls_in_psk' => true,
						'PSK identity' => 'existing_host_identity',
						'PSK' => '7c2583ef04d25c5a63f8b857d608b90e8fe63e6ddc6492af5d639d5fd8bc0573'
					]
				]
			]
		];
	}

	/**
	 * @dataProvider getMassUpdateData
	 */
	public function testEncryption_MassUpdate($data) {
		$db_query = 'SELECT * FROM hosts';
		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$old_hash = CDBHelper::getHash($db_query);
		}

		$this->page->login()->open('zabbix.php?action=host.list')->waitUntilReady();
		$table = $this->query('xpath://table[@class="list-table"]')->asTable()->one();
		$table->findRows('Name', $data['hosts'])->select();

		// Open mass update form.
		$this->query('button:Mass update')->one()->click();
		$dialog = COverlayDialogElement::find()->one()->waitUntilReady();
		$form = $dialog->asForm();
		$form->selectTab('Encryption');
		$form->invalidate();
		$form->getLabel('Connections')->click();
		$this->query('id:tls_connect')->one()->waitUntilClickable();
		$form->fill($data['psk_fields']);
		$dialog->query('button:Update')->one()->waitUntilClickable()->click();

		if (CTestArrayHelper::get($data, 'expected', TEST_GOOD) === TEST_BAD) {
			$this->assertMessage(TEST_BAD, 'Cannot update hosts',
					'Invalid parameter "/1/tls_psk": another tls_psk value is already associated with given tls_psk_identity.'
			);
			$this->assertEquals($old_hash, CDBHelper::getHash($db_query));
			$dialog->close();
		}
		else {
			$dialog->waitUntilNotVisible();
			$this->assertMessage(TEST_GOOD, 'Hosts updated');
		}
	}
}

