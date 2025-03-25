<?php
/**
 * @package     VikRentCar
 * @subpackage  mod_vikrentcar_cars
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2019 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// no direct access
defined('ABSPATH') or die('No script kiddies please!');

class Modvikrentcar_carsHelper
{
	public static function getCars($params)
	{
		$dbo = JFactory::getDBO();
		$vrc_tn = self::getTranslator();

		$showcatname = intval($params->get('showcatname')) == 1 ? true : false;
		$query = $params->get('query');

		$cars = [];

		if ($query == 'price') {
			// simple order by price asc
			$q = "SELECT `id`,`name`,`img`,`idcat`,`startfrom`,`short_info`,`idcarat` FROM `#__vikrentcar_cars` WHERE `avail`='1';";
			$dbo->setQuery($q);
			$cars = $dbo->loadAssocList();
			if ($cars) {
				$vrc_tn->translateContents($cars, '#__vikrentcar_cars');
				foreach ($cars as $k => $c) {
					$cars[$k]['catname'] = '';
					if ($showcatname && !empty($c['idcat'])) {
						$cars[$k]['catname'] = self::getCategoryName($c['idcat']);
					}
					if (!empty($c['startfrom']) && (float)$c['startfrom'] > 0) {
						$cars[$k]['cost'] = $c['startfrom'];
					} else {
						$q = "SELECT `id`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." AND `days`='1' ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
						$dbo->setQuery($q);
						$tar = $dbo->loadAssoc();
						if ($tar) {
							$cars[$k]['cost'] = $tar['cost'];
						} else {
							$q = "SELECT `id`,`days`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
							$dbo->setQuery($q);
							$tar = $dbo->loadAssoc();
							if ($tar) {
								$cars[$k]['cost'] = ($tar['cost'] / $tar['days']);
							} else {
								$cars[$k]['cost'] = 0;
							}
						}
					}
				}
			}
			$cars = self::sortCarsByPrice($cars, $params);
		} elseif ($query == 'name') {
			// order by name
			$q = "SELECT `id`,`name`,`img`,`idcat`,`startfrom`,`short_info`,`idcarat` FROM `#__vikrentcar_cars` WHERE `avail`='1' ORDER BY `#__vikrentcar_cars`.`name` ".strtoupper($params->get('order'))." LIMIT ".$params->get('numb').";";
			$dbo->setQuery($q);
			$cars = $dbo->loadAssocList();
			if ($cars) {
				$vrc_tn->translateContents($cars, '#__vikrentcar_cars');
				foreach ($cars as $k => $c) {
					$cars[$k]['catname'] = '';
					if ($showcatname && !empty($c['idcat'])) {
						$cars[$k]['catname'] = self::getCategoryName($c['idcat']);
					}
					if (!empty($c['startfrom']) && (float)$c['startfrom'] > 0) {
						$cars[$k]['cost'] = $c['startfrom'];
					} else {
						$q = "SELECT `id`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." AND `days`='1' ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
						$dbo->setQuery($q);
						$tar = $dbo->loadAssoc();
						if ($tar) {
							$cars[$k]['cost'] = $tar['cost'];
						} else {
							$q = "SELECT `id`,`days`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
							$dbo->setQuery($q);
							$tar = $dbo->loadAssoc();
							if ($tar) {
								$cars[$k]['cost'] = ($tar['cost'] / $tar['days']);
							} else {
								$cars[$k]['cost'] = 0;
							}
						}
					}
				}
			}
		} else {
			// sort by category
			$q = "SELECT `id`,`name`,`img`,`idcat`,`info`,`startfrom`,`short_info`,`idcarat` FROM `#__vikrentcar_cars` WHERE `avail`='1' AND (`idcat`='".$params->get('catid').";' OR `idcat` LIKE '".$params->get('catid').";%' OR `idcat` LIKE '%;".$params->get('catid').";%' OR `idcat` LIKE '%;".$params->get('catid').";') ORDER BY `#__vikrentcar_cars`.`name` ".strtoupper($params->get('order'))." LIMIT ".$params->get('numb').";";
			$dbo->setQuery($q);
			$cars = $dbo->loadAssocList();
			if ($cars) {
				$vrc_tn->translateContents($cars, '#__vikrentcar_cars');
				foreach ($cars as $k => $c) {
					$cars[$k]['catname'] = '';
					if ($showcatname && !empty($c['idcat'])) {
						$cars[$k]['catname'] = self::getCategoryName($c['idcat']);
					}
					if (!empty($c['startfrom']) && (float)$c['startfrom'] > 0) {
						$cars[$k]['cost'] = $c['startfrom'];
					} else {
						$q = "SELECT `id`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." AND `days`='1' ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
						$dbo->setQuery($q);
						$tar = $dbo->loadAssoc();
						if ($tar) {
							$cars[$k]['cost'] = $tar['cost'];
						} else {
							$q = "SELECT `id`,`days`,`cost` FROM `#__vikrentcar_dispcost` WHERE `idcar`=".(int)$c['id']." ORDER BY `#__vikrentcar_dispcost`.`cost` ASC LIMIT 1;";
							$dbo->setQuery($q);
							$tar = $dbo->loadAssoc();
							if ($tar) {
								$cars[$k]['cost'] = ($tar['cost'] / $tar['days']);
							} else {
								$cars[$k]['cost'] = 0;
							}
						}
					}
				}
			}
			if ($params->get('querycat') == 'price') {
				$cars = self::sortCarsByPrice($cars, $params);
			}
		}
		return $cars;
	}

	public static function sortCarsByPrice($arr, $params)
	{
		$newarr = [];
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v['cost'];
		}
		asort($newarr);
		$sorted = [];
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $params->get('order') == 'desc' ? array_reverse($sorted) : $sorted;
	}

	public static function getCategoryName($idcat)
	{
		$dbo = JFactory::getDBO();

		$cat_ids = [];
		if (!empty($idcat)) {
			$cat_ids = explode(';', $idcat);
			$cat_ids = array_filter(array_map('intval', $cat_ids));
		}

		if (!$cat_ids) {
			return '';
		}

		$q = "SELECT `id`,`name` FROM `#__vikrentcar_categories` WHERE `id` IN (" . implode(', ', $cat_ids) . ") ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$categories = $dbo->loadAssocList();

		if (!$categories) {
			return '';
		}

		self::getTranslator()->translateContents($p, '#__vikrentcar_categories');

		return implode(', ', array_column($categories, 'name'));
	}

	public static function limitRes($cars, $params)
	{
		return array_slice($cars, 0, $params->get('numb'));
	}

	public static function getTranslator()
	{
		return VikRentCar::getTranslator();
	}

	public static function numberFormat($numb)
	{
		return VikRentCar::numberFormat($numb);
	}

	public static function getCarCaratOriz($idc, $map = array(), $vrc_tn = null)
	{
		$dbo = JFactory::getDBO();
		$split = explode(";", $idc);
		$carat = "";
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = (int)$s;
			}
		}
		if ($where) {
			if ($map) {
				foreach ($where as $c_id) {
					if (array_key_exists($c_id, $map)) {
						$arr[] = $map[$c_id];
					}
				}
			} else {
				$q = "SELECT * FROM `#__vikrentcar_caratteristiche` WHERE `id` IN (".implode(",", $where).") ORDER BY `#__vikrentcar_caratteristiche`.`ordering` ASC;";
				$dbo->setQuery($q);
				$arr = $dbo->loadAssocList();
				if ($arr && is_object($vrc_tn)) {
					$vrc_tn->translateContents($arr, '#__vikrentcar_caratteristiche');
				}
			}
		}
		if ($arr) {
			$carat .= "<div class=\"vrccaratsdiv\">";
			foreach ($arr as $a) {
				$carat .= "<div class=\"vrccarcarat\">";
				if (!empty ($a['textimg'])) {
					//tooltip icon text is not empty
					if (!empty($a['icon'])) {
						//an icon has been uploaded: display the image
						$carat .= "<span class=\"vrc-carat-cont\"><span class=\"vrc-expl\" data-vrc-expl=\"".$a['textimg']."\"><img src=\"".VRC_ADMIN_URI."resources/".$a['icon']."\" alt=\"" . $a['name'] . "\" /></span></span>\n";
					} else {
						if (strpos($a['textimg'], '</i>') !== false) {
							//the tooltip icon text is a font-icon, we can use the name as tooltip
							$carat .= "<span class=\"vrc-carat-cont\"><span class=\"vrc-expl\" data-vrc-expl=\"".$a['name']."\">".$a['textimg']."</span></span>\n";
						} else {
							//display just the text
							$carat .= "<span class=\"vrc-carat-cont\">".$a['textimg']."</span>\n";
						}
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<span class=\"vrc-carat-cont\"><img src=\"".VRC_ADMIN_URI."resources/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></span>\n" : "<span class=\"vrc-carat-cont\">".$a['name']."</span>\n");
				}
				$carat .= "</div>";
			}
			$carat .= "</div>\n";
		}
		return $carat;
	}
	
}
