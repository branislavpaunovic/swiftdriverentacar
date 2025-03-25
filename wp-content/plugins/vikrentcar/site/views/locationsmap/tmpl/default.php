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

$locations = $this->locations;
$vrc_tn = $this->vrc_tn;

if ($locations) {
	$lats = array();
	$lngs = array();
	foreach($locations as $l) {
		$lats[] = $l['lat'];
		$lngs[] = $l['lng'];
	}
	$document = JFactory::getDocument();
	$gmap_key = VikRentCar::getGoogleMapsKey();
	$document->addScript((strpos(JURI::root(), 'https') !== false ? 'https' : 'http').'://maps.google.com/maps/api/js'.(!empty($gmap_key) ? '?key='.$gmap_key : ''));
	?>
	<script type="text/javascript">
	function vrcSetLocOpenTimeFrame(loc, where) {
		jQuery.ajax({
			type: "POST",
			url: "<?php echo VikRentCar::ajaxUrl('index.php?option=com_vikrentcar&task=ajaxlocopentime&tmpl=component', false); ?>",
			data: { idloc: loc, pickdrop: where }
		}).done(function(res) {
			var vrcobj = JSON.parse(res);
			if (where == "pickup") {
				jQuery("#vrccomselph", window.parent.document).html(vrcobj.hours);
				jQuery("#vrccomselpm", window.parent.document).html(vrcobj.minutes);
			} else {
				jQuery("#vrccomseldh", window.parent.document).html(vrcobj.hours);
				jQuery("#vrccomseldm", window.parent.document).html(vrcobj.minutes);
			}
		});
	}
	jQuery(function() {
		setTimeout(function() {
			var map = new google.maps.Map(document.getElementById("vrcmapcanvas"), {mapTypeId: google.maps.MapTypeId.ROADMAP});
			<?php
			foreach($locations as $l) {
				?>
			var marker<?php echo $l['id']; ?> = new google.maps.Marker({
				position: new google.maps.LatLng(<?php echo $l['lat']; ?>, <?php echo $l['lng']; ?>),
				map: map,
				title: '<?php echo addslashes($l['name']); ?>'
			});	
				<?php
				if (strlen(trim(strip_tags($l['descr']))) > 0) {
					?>	
			var tooltip<?php echo $l['id']; ?> = '<div class="vrcgmapinfow"><h3><?php echo addslashes($l['name']); ?></h3><div class="vrcgmapinfowdescr"><?php echo addslashes(preg_replace('/\s\s+/', ' ', $l['descr'])); ?></div></div>';
			var infowindow<?php echo $l['id']; ?> = new google.maps.InfoWindow({
				content: tooltip<?php echo $l['id']; ?>
			});
			google.maps.event.addListener(marker<?php echo $l['id']; ?>, 'click', function() {
				infowindow<?php echo $l['id']; ?>.open(map, marker<?php echo $l['id']; ?>);
				var parentsel = parent.document.getElementById('place');
				if (typeof(parentsel) != 'undefined' && parentsel != null) {
					parentsel.selectedIndex = parent.document.getElementById('place<?php echo $l['id']; ?>').index;
					parent.document.getElementById('returnplace').selectedIndex = parent.document.getElementById('returnplace<?php echo $l['id']; ?>').index;
					vrcSetLocOpenTimeFrame(<?php echo $l['id']; ?>, 'pickup');
					vrcSetLocOpenTimeFrame(<?php echo $l['id']; ?>, 'dropoff');
				}
			});
					<?php
				}
			}
			?>
			
			var lat_min = <?php echo min($lats); ?>;
			var lat_max = <?php echo max($lats); ?>;
			var lng_min = <?php echo min($lngs); ?>;
			var lng_max = <?php echo max($lngs); ?>;
			
			map.setCenter(new google.maps.LatLng( ((lat_max + lat_min) / 2.0), ((lng_max + lng_min) / 2.0) ));
			map.fitBounds(new google.maps.LatLngBounds(new google.maps.LatLng(lat_min, lng_min), new google.maps.LatLng(lat_max, lng_max)));
		}, 1000);
		
	});
	</script>
	<div id="vrcmapcanvas" style="width: 700px; height: 550px;"></div>
	<?php
}
VikRentCar::printTrackingCode();
?>