<?php
/**
 * @package     VikRentCar
 * @subpackage  com_vikrentcar
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

class VikRentCarHelper
{
	public static function printHeader($highlight = "")
	{
		$app = JFactory::getApplication();
		$cookie = $app->input->cookie;
		$tmpl = VikRequest::getVar('tmpl');
		$view = VikRequest::getVar('view');

		if ($tmpl == 'component') {
			return;
		}

		if (VRCPlatformDetection::isWordPress()) {
			/**
			 * @wponly Hide menu for Pro-update views
			 */
			if (in_array($view, ['getpro'])) {
				return;
			}
		}

		// JS lang def
		JText::script('VRC_QUICK_ACTIONS');

		$session 	= JFactory::getSession();
		$admin_user = JFactory::getUser();

		$backlogo = VikRentCar::getBackendLogo();
		$vrc_auth_cars = $admin_user->authorise('core.vrc.cars', 'com_vikrentcar');
		$vrc_auth_prices = $admin_user->authorise('core.vrc.prices', 'com_vikrentcar');
		$vrc_auth_orders = $admin_user->authorise('core.vrc.orders', 'com_vikrentcar');
		$vrc_auth_gsettings = $admin_user->authorise('core.vrc.gsettings', 'com_vikrentcar');
		$vrc_auth_management = $admin_user->authorise('core.vrc.management', 'com_vikrentcar');

		// check for stored quick actions only once
		$admin_menu_actions_checked = $session->get('admin_menu.actions.check', null, 'vikrentcar');
		if (!$admin_menu_actions_checked) {
			$session->set('admin_menu.actions.check', 1, 'vikrentcar');
		}

		?>
		<div class="vrc-menu-container<?php echo $view == 'dashboard' ? ' vrc-menu-container-closer' : ''; ?>">
			<div class="vrc-menu-left">
				<a href="index.php?option=com_vikrentcar"><img src="<?php echo VRC_ADMIN_URI . (!empty($backlogo) ? 'resources/'.$backlogo : 'vikrentcar.png'); ?>" alt="VikRentCar Logo" /></a>
			</div>
			<div class="vrc-menu-right">
				<ul class="vrc-menu-ul">
					<?php
					if ($vrc_auth_prices || $vrc_auth_gsettings) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('key'); ?> <a><?php echo JText::translate('VRMENUONE'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="rental">
							<?php if ($vrc_auth_prices) : ?>
								<li>
									<div class="<?php echo ($highlight == "2" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=iva">
											<?php VikRentCarIcons::e('percent'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUNINE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "1" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=prices">
											<?php VikRentCarIcons::e('tags'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUFIVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vrc_auth_gsettings) : ?>
								<li>
									<div class="<?php echo ($highlight == "3" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=places">
											<?php VikRentCarIcons::e('map-marker'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENTHREE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							<?php if ($vrc_auth_prices) : ?>
								<li>
									<div class="<?php echo ($highlight == "restrictions" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=restrictions">
											<?php VikRentCarIcons::e('hand-paper'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENURESTRICTIONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
							</ul>
						</div>
					</li><?php
					}
					if ($vrc_auth_cars) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('car'); ?> <a><?php echo JText::translate('VRMENUTWO'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="cars">
								<li>
									<div class="<?php echo ($highlight == "4" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=categories">
											<?php VikRentCarIcons::e('filter'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUSIX'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "6" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=optionals">
											<?php VikRentCarIcons::e('car-crash'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENFIVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "5" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=carat">
											<?php VikRentCarIcons::e('snowflake'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENFOUR'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "7" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=cars">
											<?php VikRentCarIcons::e('car'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					if ($vrc_auth_prices) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('calculator'); ?> <a><?php echo JText::translate('VRCMENUFARES'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="pricing">
								<li>
									<div class="<?php echo ($highlight == "fares" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=tariffs">
											<?php VikRentCarIcons::e('toolbox'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUPRICESTABLE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "13" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=seasons">
											<?php VikRentCarIcons::e('seedling'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENSEVEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "12" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=locfees">
											<?php VikRentCarIcons::e('globe'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENSIX'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "20" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=oohfees">
											<?php VikRentCarIcons::e('clock'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUOOHFEES'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "ratesoverv" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=ratesoverv">
											<?php VikRentCarIcons::e('calculator'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENURATESOVERVIEW'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('calendar-check'); ?> <a><?php echo JText::translate('VRMENUTHREE'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="orders">
							<?php if ($vrc_auth_orders) : ?>
								<li>
									<div class="<?php echo ($highlight == "8" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=orders">
											<?php VikRentCarIcons::e('clipboard-list'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUSEVEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "19" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=calendar">
											<?php VikRentCarIcons::e('calendar'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUQUICKRES'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "15" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=overv">
											<?php VikRentCarIcons::e('calendar-check'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENNINE'); ?></span>
											</span>
										</a>
									</div>
								</li>
							<?php endif; ?>
								<li>
									<div class="<?php echo ($highlight == "18" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar">
											<?php VikRentCarIcons::e('concierge-bell'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUDASHBOARD'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					if ($vrc_auth_management) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('chart-line'); ?> <a><?php echo JText::translate('VRCMENUMANAGEMENT'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="management">
								<li>
									<div class="<?php echo ($highlight == "customers" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=customers">
											<?php VikRentCarIcons::e('users'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUCUSTOMERS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "17" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=coupons">
											<?php VikRentCarIcons::e('user-tag'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUCOUPONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "22" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=graphs">
											<?php VikRentCarIcons::e('chart-line'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUGRAPHS'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					if ($vrc_auth_management) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('balance-scale'); ?> <a><?php echo JText::translate('VRCMENUADV'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="advanced">
								<li>
									<div class="<?php echo (in_array($highlight, ["crons", "managecron"]) ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;view=crons">
											<?php VikRentCarIcons::e('stopwatch'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUCRONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "pmsreports" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=pmsreports">
											<?php VikRentCarIcons::e('wallet'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUPMSREPORTS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "trackings" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=trackings">
											<?php VikRentCarIcons::e('compass'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRCMENUTRACKINGS'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					if ($vrc_auth_gsettings) {
					?><li class="vrc-menu-parent-li">
						<span><?php VikRentCarIcons::e('cogs'); ?> <a><?php echo JText::translate('VRMENUFOUR'); ?> <?php VikRentCarIcons::e('chevron-down', 'vrc-submenu-chevron'); ?></a></span>
						<div class="vrc-submenu-wrap">
							<ul class="vrc-submenu-ul" data-menu-scope="global">
								<li>
									<div class="<?php echo ($highlight == "11" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=config">
											<?php VikRentCarIcons::e('cogs'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTWELVE'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "21" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=translations">
											<?php VikRentCarIcons::e('language'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTRANSLATIONS'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "14" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=payments">
											<?php VikRentCarIcons::e('credit-card'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENEIGHT'); ?></span>
											</span>
										</a>
									</div>
								</li>
								<li>
									<div class="<?php echo ($highlight == "16" ? "vmenulinkactive" : "vmenulink"); ?>">
										<a href="index.php?option=com_vikrentcar&amp;task=customf">
											<?php VikRentCarIcons::e('address-card'); ?>
											<span class="vrc-submenu-item">
												<span class="vrc-submenu-item-txt"><?php echo JText::translate('VRMENUTENTEN'); ?></span>
											</span>
										</a>
									</div>
								</li>
							</ul>
						</div>
					</li><?php
					}
					?>
				</ul>
				<div class="vrc-menu-updates">
			<?php
			if (VRCPlatformDetection::isWordPress()) {
				/**
				 * @wponly PRO Version
				 */
				VikRentCarLoader::import('update.license');
				if (!VikRentCarLicense::isPro()) {
					?>
					<button type="button" class="vrc-gotopro" onclick="document.location.href='admin.php?option=com_vikrentcar&view=gotopro';">
						<?php VikRentCarIcons::e('rocket'); ?>
						<span><?php echo JText::translate('VRCGOTOPROBTN'); ?></span>
					</button>
					<?php
				} else {
					?>
					<button type="button" class="vrc-alreadypro" onclick="document.location.href='admin.php?option=com_vikrentcar&view=gotopro';">
						<?php VikRentCarIcons::e('trophy'); ?>
						<span><?php echo JText::translate('VRCISPROBTN'); ?></span>
					</button>
					<?php
				}
			} else {
				/**
				 * @joomlaonly
				 */
				if (($highlight == '18' || $highlight == '11') && method_exists($app, 'triggerEvent')) {
					//VikUpdater
					JPluginHelper::importPlugin('e4j');
					$callable = $app->triggerEvent('onUpdaterSupported');
					if (count($callable) && $callable[0]) {
						//Plugin enabled
						$params = new stdClass;
						$params->version 	= E4J_SOFTWARE_VERSION;
						$params->alias 		= 'com_vikrentcar';
						
						$upd_btn_text = strrev('setadpU kcehC');
						$ready_jsfun = '';
						$result = $app->triggerEvent('onGetVersionContents', array(&$params));
						if (count($result) && $result[0]) {
							$upd_btn_text = $result[0]->response->shortTitle;
						} else {
							$ready_jsfun = 'jQuery("#vik-update-btn").trigger("click");';
						}
						?>
						<button type="button" id="vik-update-btn" onclick="<?php echo count($result) && $result[0] && $result[0]->response->compare == 1 ? 'document.location.href=\'index.php?option=com_vikrentcar&task=updateprogram\'' : 'checkVersion(this);'; ?>">
							<?php VikRentCarIcons::e('cloud'); ?>
							<span><?php echo $upd_btn_text; ?></span>
						</button>
						<script type="text/javascript">
						function checkVersion(button) {
							jQuery(button).find('span').text('Checking...');
							jQuery.ajax({
								type: 'POST',
								url: 'index.php?option=com_vikrentcar&task=checkversion&tmpl=component',
								data: {}
							}).done(function(resp){
								var obj = JSON.parse(resp);
								console.log(obj);
								if (obj.status == 1 && obj.response.status == 1) {
									jQuery(button).find('span').text(obj.response.shortTitle);
									if (obj.response.compare == 1) {
										jQuery(button).attr('onclick', 'document.location.href="index.php?option=com_vikrentcar&task=updateprogram"');
									}
								}
							}).fail(function(resp){
								console.log(resp);
							});
						}
						jQuery(document).ready(function() {
							<?php echo $ready_jsfun; ?>
						});
						</script>
						<?php
					} else {
						/**
						 * When Vik Updater is not available or disabled, we now
						 * render a modal for the automated installation of the plugin.
						 * 
						 * @since 	1.14.6
						 */

						$data = [
							'hn'  => getenv('HTTP_HOST'),
							'sn'  => getenv('SERVER_NAME'),
							'app' => CREATIVIKAPP,
							'ver' => VIKRENTCAR_SOFTWARE_VERSION,
						];

						$vikupdater_url = 'https://extensionsforjoomla.com/vikcheck/vikupdater.php?' . http_build_query($data);

						echo JHtml::fetch(
							'bootstrap.renderModal',
							'jmodal-version-check',
							array(
								'title'       => 'Install VikUpdater',
								'closeButton' => true,
								'keyboard'    => true,
								'bodyHeight'  => 80,
								'url'         => $vikupdater_url,
								'footer'      => '<button type="button" class="btn btn-success" id="version-check-install">' . JText::translate('JTOOLBAR_INSTALL') . '</button>',
							)
						);
						?>
						<button type="button" id="vik-update-btn">
							<?php VikRentCarIcons::e('cloud'); ?>
							<span></span>
						</button>

						<?php echo VikRentCar::getVrcApplication()->getJmodalScript(); ?>

						<script>
							(function($) {
								'use strict';

								$(function() {
									$('#vik-update-btn').on('click', () => {
										vrcOpenJModal('version-check');
									});

									$('#version-check-install').on('click', () => {
										const form = $('<form action="index.php?option=com_installer&task=install.install" method="post"></form>');

										form.append('<input type="hidden" name="installtype" value="url" />');
										form.append('<input type="hidden" name="install_url" value="https://extensionsforjoomla.com/vikapi/?task=products.freedownload&sku=vup" />');
										form.append('<input type="hidden" name="return" value="<?php echo base64_encode(JUri::getInstance()); ?>" />');
										form.append('<?php echo JHtml::fetch('form.token'); ?>');

										$('body').append(form);

										form.submit();
									});
								});
							})(jQuery);
						</script>
						<?php
					}
				}
			}
			?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		jQuery(function() {
			jQuery('.vrc-menu-parent-li').hover(
				function() {
					jQuery(this).addClass('vrc-menu-parent-li-opened');
					jQuery(this).find('.vrc-submenu-wrap').addClass('vrc-submenu-wrap-active');
				},function() {
					jQuery(this).removeClass('vrc-menu-parent-li-opened');
					jQuery(this).find('.vrc-submenu-wrap').removeClass('vrc-submenu-wrap-active');
				}
			);

			if (jQuery('.vmenulinkactive').length) {
				// set active class to current menu block
				jQuery('.vmenulinkactive').closest('.vrc-submenu-wrap').parent('li').addClass('vrc-menu-parent-li-active');
			}

			// handle quick actions storage
			jQuery('.vrc-menu-right').find('.vrc-submenu-ul').find('a').on('click', function(e) {
				if (!jQuery(this).find('.vrc-submenu-item-txt').length) {
					// nothing to do
					return true;
				}

				// handle the clicked menu entry
				e.preventDefault();

				try {
					// register clicked page
					VRCCore.registerAdminMenuAction({
						name: jQuery(this).find('.vrc-submenu-item-txt').text(),
						href: jQuery(this).attr('href'),
					});
				} catch(e) {
					console.error(e);
				}

				// proceed with the navigation
				window.location.href = jQuery(this).attr('href');

				return true;
			});

			// register event to populate quick actions sub-menu helpers
			document.addEventListener('vrc-adminmenu-quickactions-create', () => {
				jQuery('.vrc-submenu-ul[data-menu-scope]').each(function() {
					let menu_ul = jQuery(this);
					let scope = menu_ul.attr('data-menu-scope');
					if (!scope) {
						return;
					}
					let wrapper = menu_ul.closest('.vrc-submenu-wrap');
					if (!wrapper || !wrapper.length || wrapper.hasClass('vrc-submenu-wrap-multi') || wrapper.find('.vrc-submenu-helper-ul').length) {
						return;
					}
					let menu_scope_actions = VRCCore.getAdminMenuActions(scope);
					if (!Array.isArray(menu_scope_actions) || !menu_scope_actions.length) {
						return;
					}
					wrapper.addClass('vrc-submenu-wrap-multi');
					let quick_actions = jQuery('<ul></ul>').addClass('vrc-submenu-helper-ul');
					quick_actions.append('<li class="vrc-submenu-helper-lbl-li"><span class="vrc-submenu-helper-lbl-txt">' + Joomla.JText._('VRC_QUICK_ACTIONS') + '</span></li>');
					menu_scope_actions.forEach((action, index) => {
						let is_pinned = action.hasOwnProperty('pinned') && action['pinned'];
						let quick_actions_entry = jQuery('<li></li>').addClass((is_pinned ? 'vrc-submenu-item-helper-pinned' : 'vrc-submenu-item-helper-unpinned'));
						let quick_actions_div = jQuery('<div></div>').addClass('vmenulink');
						let quick_action_link = jQuery('<a></a>').attr('href', action['href']).addClass('vrc-submenu-item-helper-link');
						if (action.hasOwnProperty('target') && action['target']) {
							quick_action_link.attr('target', action['target']);
						}
						if (action.hasOwnProperty('img') && action['img']) {
							let quick_action_img = jQuery('<span></span>').addClass('vrc-submenu-item-helper-avatar');
							quick_action_img.append('<img src="' + action['img'] + '" />');
							quick_action_link.append(quick_action_img);
						}
						let quick_action_name = jQuery('<span></span>').addClass('vrc-submenu-item-helper-txt').text(action['name']);
						quick_action_link.append(quick_action_name);
						quick_actions_div.append(quick_action_link);
						let quick_action_pin = jQuery('<span></span>').addClass('vrc-submenu-item-helper-setpin').on('click', function() {
							// toggle pinned status and update admin menu action
							if (!action.hasOwnProperty('pinned')) {
								action['pinned'] = !is_pinned;
							} else {
								action['pinned'] = !action['pinned'];
							}
							try {
								// update local storage
								VRCCore.updateAdminMenuAction(action, scope);
								// trigger event
								VRCCore.emitEvent('vrc-adminmenu-quickactions-update');
							} catch(e) {
								console.error(e);
							}
							// update action status
							if (action['pinned']) {
								jQuery(this).closest('li').removeClass('vrc-submenu-item-helper-unpinned').addClass('vrc-submenu-item-helper-pinned');
							} else {
								jQuery(this).closest('li').removeClass('vrc-submenu-item-helper-pinned').addClass('vrc-submenu-item-helper-unpinned');
							}
						});
						quick_action_pin.html('<?php VikRentCarIcons::e('thumbtack'); ?>');
						quick_actions_div.append(quick_action_pin);
						quick_actions_entry.append(quick_actions_div);
						quick_actions.append(quick_actions_entry);
					});
					wrapper.append(quick_actions);
				});
			});

			// register event to update the pinned quick actions
			document.addEventListener('vrc-adminmenu-quickactions-update', VRCCore.debounceEvent(() => {
				let menu_scopes = [];
				jQuery('.vrc-submenu-ul[data-menu-scope]').each(function() {
					let menu_ul = jQuery(this);
					let scope = menu_ul.attr('data-menu-scope');
					if (!scope) {
						scope = '';
					}
					if (menu_scopes.indexOf(scope) < 0) {
						menu_scopes.push(scope);
					}
				});
				let admin_menu_actions = [];
				menu_scopes.forEach((scope) => {
					let menu_actions = VRCCore.getAdminMenuActions(scope);
					admin_menu_actions.push({
						scope: scope,
						actions: menu_actions,
					});
				});
				VRCCore.doAjax(
					"<?php echo VikRentCar::ajaxUrl('index.php?option=com_vikrentcar&task=menuactions.update'); ?>",
					{
						actions: admin_menu_actions,
					},
					(resp) => {
						// do nothing
					},
					(err) => {
						// log the error
						console.error(err.responseText);
					}
				);
			}, 300));

			// populate quick actions sub-menu helpers on page load
			VRCCore.emitEvent('vrc-adminmenu-quickactions-create');

			// check (only once) if the quick actions should be imported from the db
			if (<?php echo !$admin_menu_actions_checked ? 'true' : 'false'; ?>) {
				setTimeout(() => {
					// count admin menu actions populated from local storage
					let tot_admin_menu_actions = 0;
					jQuery('.vrc-submenu-ul[data-menu-scope]').each(function() {
						let menu_ul = jQuery(this);
						let scope = menu_ul.attr('data-menu-scope');
						if (!scope) {
							return;
						}
						let menu_scope_actions = VRCCore.getAdminMenuActions(scope);
						if (!Array.isArray(menu_scope_actions) || !menu_scope_actions.length) {
							return;
						}
						tot_admin_menu_actions++;
						return false;
					});

					if (tot_admin_menu_actions) {
						return;
					}

					// request for any previously stored quick actions for this admin
					VRCCore.doAjax(
						"<?php echo VikRentCar::ajaxUrl('index.php?option=com_vikrentcar&task=menuactions.retrieve'); ?>",
						{},
						(resp) => {
							// store to local storage the quick actions just retrieved
							let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
							if (Array.isArray(obj_res) && obj_res.length) {
								obj_res.forEach((menu_actions) => {
									let storage_scope_name = VRCCore.options.admin_menu_actions_nm;
									if (menu_actions['scope']) {
										storage_scope_name += '.' + menu_actions['scope'];
									}
									VRCCore.storageSetItem(storage_scope_name, menu_actions['actions']);
								});
							}
							// trigger the event to populate the quick actions
							VRCCore.emitEvent('vrc-adminmenu-quickactions-create');
						},
						(err) => {
							// log the error
							console.error(err.responseText);
						}
					);
				}, 300);
			}
		});
		</script>
		<?php
	}

	public static function printFooter()
	{
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') {
			return;
		}

		if (VRCPlatformDetection::isWordPress()) {
			echo '<br clear="all" />' . '<div id="hmfooter">' . JText::sprintf('VRCVERSION', VIKRENTCAR_SOFTWARE_VERSION) . ' <a href="https://vikwp.com/" target="_blank">VikWP - vikwp.com</a></div>';
		} else {
			echo '<br clear="all" />' . '<div id="hmfooter">' . JText::sprintf('VRCVERSION', E4J_SOFTWARE_VERSION) . ' <a href="https://extensionsforjoomla.com/">e4j - Extensionsforjoomla.com</a></div>';
		}
	}

	public static function pUpdateProgram($version)
	{
		/**
		 * @wponly 	do nothing
		 */
	}

	/**
	 * Method to add parameters to the update extra query.
	 * 
	 * @joomlaonly 	this class is automatically loaded by Joomla
	 * 				to invoke this method when updating the component.
	 *
	 * @param   Update  &$update  An update definition
	 * @param   JTable  &$table   The update instance from the database
	 *
	 * @return  void
	 *
	 * @since 	1.15.1 (J) - 1.3.1 (WP)
	 */
	public static function prepareUpdate(&$update, &$table)
	{
		// get current domain
		$server = JFactory::getApplication()->input->server;

		// build query array
		$query = [
			'domain' => base64_encode($server->getString('HTTP_HOST')),
			'ip' 	 => $server->getString('REMOTE_ADDR'),
		];

		// always refresh the extra query before an update
		$update->set('extra_query', http_build_query($query, '', '&amp;'));
	}
}
