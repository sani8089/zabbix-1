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


require_once __DIR__.'/../../include/forms.inc.php';

class CControllerPopupMassupdateService extends CController {

	protected function checkInput(): bool {
		$fields = [
			'ids' =>				'required|array_id',
			'update' =>				'in 1',
			'visible' =>			'array',
			'tags' =>				'array',
			'mass_update_tags' =>	'in '.implode(',', [ZBX_ACTION_ADD, ZBX_ACTION_REPLACE, ZBX_ACTION_REMOVE]),
			'location_url' => 		'required|string'
		];

		$ret = $this->validateInput($fields);

		if (!$ret) {
			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode([
					'error' => [
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				])]))->disableView()
			);
		}

		return $ret;
	}

	/**
	 * @throws APIException
	 */
	protected function checkPermissions(): bool {
		if (!$this->checkAccess(CRoleHelper::UI_SERVICES_SERVICES)) {
			return false;
		}

		return (bool) API::Service()->get([
			'output' => [],
			'serviceids' => $this->getInput('ids')
		]);
	}

	protected function doAction(): void {
		if ($this->hasInput('update')) {
			$serviceids = $this->getInput('ids', []);
			$visible = $this->getInput('visible', []);
			$tags = array_filter($this->getInput('tags', []),
				static function (array $tag): bool {
					return ($tag['tag'] !== '' || $tag['value'] !== '');
				}
			);

			$result = true;

			try {
				$options = [
					'output' => ['serviceid'],
					'serviceids' => $serviceids
				];

				if (array_key_exists('tags', $visible)) {
					$mass_update_tags = $this->getInput('mass_update_tags', ZBX_ACTION_ADD);

					if ($mass_update_tags == ZBX_ACTION_ADD || $mass_update_tags == ZBX_ACTION_REMOVE) {
						$options['selectTags'] = ['tag', 'value'];
					}

					$unique_tags = [];
					foreach ($tags as $tag) {
						$unique_tags[$tag['tag']][$tag['value']] = $tag;
					}

					$tags = [];
					foreach ($unique_tags as $tag) {
						foreach ($tag as $value) {
							$tags[] = $value;
						}
					}
				}

				$services = API::Service()->get($options);

				foreach ($services as &$service) {
					if (array_key_exists('tags', $visible)) {
						if ($tags && $mass_update_tags == ZBX_ACTION_ADD) {
							$unique_tags = [];

							foreach (array_merge($service['tags'], $tags) as $tag) {
								$unique_tags[$tag['tag']][$tag['value']] = $tag;
							}

							$service['tags'] = [];
							foreach ($unique_tags as $tag) {
								foreach ($tag as $value) {
									$service['tags'][] = $value;
								}
							}
						}
						elseif ($mass_update_tags == ZBX_ACTION_REPLACE) {
							$service['tags'] = $tags;
						}
						elseif ($tags && $mass_update_tags == ZBX_ACTION_REMOVE) {
							$diff_tags = [];

							foreach ($service['tags'] as $a) {
								foreach ($tags as $b) {
									if ($a['tag'] === $b['tag'] && $a['value'] === $b['value']) {
										continue 2;
									}
								}

								$diff_tags[] = $a;
							}

							$service['tags'] = $diff_tags;
						}
					}
				}
				unset($service);

				if (!API::Service()->update($services)) {
					throw new Exception();
				}
			}
			catch (Exception $e) {
				$result = false;
			}

			if ($result) {
				$output = ['title' => _('Services updated')];

				if ($messages = CMessageHelper::getMessages()) {
					$output['messages'] = array_column($messages, 'message');
				}
			}
			else {
				$output = [
					'error' => [
						'title' => _('Cannot update services'),
						'messages' => array_column(get_and_clear_messages(), 'message')
					]
				];
			}

			$this->setResponse(
				(new CControllerResponseData(['main_block' => json_encode($output)]))->disableView()
			);
		}
		else {
			$data = [
				'title' => _('Mass update'),
				'user' => [
					'debug_mode' => $this->getDebugMode()
				],
				'ids' => $this->getInput('ids'),
				'location_url' => $this->getInput('location_url')
			];

			$this->setResponse(new CControllerResponseData($data));
		}
	}
}
