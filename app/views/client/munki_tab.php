<?php $client = new Munkireport_model($serial_number); ?>

<?php $report = $client->report_plist; ?>

<?php if( ! $report): ?>
	<p><i>No Munkireport data</i></p>
	<?php return; ?>
<?php endif; ?>

<div class="row">

		<div class="col-lg-6">

		<h2 id="errors">Errors &amp; Warnings</h2>

		<?php if($client->report_plist['Errors'] OR $client->report_plist['Warnings']): ?>

			<?php if($client->report_plist['Errors']): ?>
				<pre class="alert alert-danger">• <?php echo implode("\n• ", $client->report_plist['Errors']); ?></pre>
			<?php endif; ?>

			<?php if($client->report_plist['Warnings']): ?>
				<pre class="alert alert-warning">• <?php echo implode("\n• ", $client->report_plist['Warnings']); ?></pre>
			<?php endif; ?>

		<?php else: ?>
			<p><i>No errors or warnings</i></p>
		<?php endif ?>

	</div><!-- </div class="col-lg-6"> -->

	<div class="col-lg-6">

		<h2>Munki</h2>
		<table class="table table-striped">
			<tr>
				<th>Version:</th>
				<td><?php echo $client->version; ?></td>
			</tr>
			<tr>
				<th>SoftwareRepoURL:</th>
				<td><div id="munkiinfo-SoftwareRepoURL"></div></td>
			</tr>
			<tr>
				<th>AppleCatalogURL:</th>
				<td><div id="munkiinfo-AppleCatalogURL"></div></td>
			</tr>
			<tr>
				<th>Manifest:</th>
				<td><?php echo $client->manifestname; ?></td>
			</tr>
			<tr>
				<th>LocalOnlyManifest:</th>
				<td><div id="munkiinfo-LocalOnlyManifest"></div></td>
			</tr>
			<tr>
				<th>Run Type:</th>
				<td><?php echo $client->runtype; ?></td>
			</tr>
			<tr>
				<th>Start:</th>
				<td><time datetime="<?php echo $client->starttime; ?>"></time></td>
			</tr>
			<tr>
				<?php $duration = strtotime($client->endtime) - strtotime($client->starttime); ?>
				<th>Duration:</th>
				<td><?php echo $duration; ?> seconds</td>
			</tr>
		</table>
	</div><!-- </div class="col-lg-6"> -->

	<!-- <Additional Munki Info> -->
  <style>
    /* Popover */
    .popover {
      border-bottom:1px solid #ebebeb;
      -webkit-border-radius:5px 5px 0 0;
      -moz-border-radius:5px 5px 0 0;
      border-radius:5px 5px 0 0;
      width:550px;
    }
    .munkiinfo {
      position: relative;
      top: -15px;
      left: 15px;
    }
    
  </style>
  
  <button id="popoverId" class="popoverThis btn btn-info btn-sm munkiinfo"><b>Additional Munki Info</b></button>
  <div id="munkiinfo-prefs-table" style="display: none"></div>
	<!-- </Additional Munki Info> -->


<script>
		$(document).on('appReady', function(e, lang) {
			$( "table time" ).each(function( index ) {
				$(this).html(moment($(this).attr('datetime'), "YYYY-MM-DD HH:mm:ss Z").fromNow());
			});
		});
</script>

<script>
$(document).on('appReady', function(){
  $.getJSON(appUrl + '/module/munkiinfo/get_data/' + serialNumber, function(data){
    // These are single preferences
    $('#munkiinfo-SoftwareRepoURL').text(data['SoftwareRepoURL']);
    $('#munkiinfo-AppleCatalogURL').text(data['AppleCatalogURL']);
    $('#munkiinfo-LocalOnlyManifest').text(data['LocalOnlyManifest']);
    
    // Create table of all preferences
    var rows = ''
    for (key in data){
      rows = rows + '<tr><th>'+key+': </th><td>'+data[key]+'</td></tr>'
    }
      $("#munkiinfo-prefs-table")
			.append($('<div>')
      .append('<a target="_blank" href="https://github.com/munki/munki/wiki/Preferences#supported-managedinstalls-keys">Supported Managedinstalls Keys</a>')
        .addClass('table-responsive')
        .append($('<table>')
          // .append('<caption>Additional Munki Info</caption>')
					.addClass('table table-striped')
					.append($('<tbody>')
						.append(rows))))
  });
});
</script>

<script>
// Pop-up button - Credit http://jsfiddle.net/kAYyR/547/
$(document).ready(function(){
  $('#popoverId').popover({
      html: true,
      // title: 'Popover Title<a class="close" href="#");">&times;</a>',
      // content: $('#popoverContent').html(),
      content: function() {
        return $('#munkiinfo-prefs-table').html();
      }
  });
});
$(document).ready(function(){
  $('#popoverId').click(function (e) {
      e.stopPropagation();
  });
});

$(document).click(function (e) {
    if (($('.popover').has(e.target).length == 0) || $(e.target).is('.close')) {
        $('#popoverId').popover('hide');
    }
});
</script>

<?php // Move install results over to their install items.
$install_results = array();
if(isset($report['InstallResults']))
{
	foreach($report['InstallResults'] as $result)
	{
		$install_results[$result["name"] . '-' .$result["version"]] = 
			array('result' => $result["status"] == 0 ? 'Installed' : 'error');
	}
}
foreach(array('ItemsToInstall', 'AppleUpdates') AS $r_item)
{
	if(isset($report[$r_item]))
	{
		foreach($report[$r_item] as $key => &$item)
		{
			$item['install_result'] = 'Pending';
			$dversion = $report[$r_item][$key]["name"].'-'.$report[$r_item][$key]["version_to_install"];
			if(isset($install_results[$dversion]))
			{
				$item['install_result'] = $install_results[$dversion]['result'];
			}
		}
		unset($item);
	}
}		

// Move install results to managed installs
if(isset($report['ManagedInstalls']))
{
	foreach($report['ManagedInstalls'] as $key => $item)
	{
		if(isset($item["version_to_install"]))
		{
			$dversion = $item["name"].'-'.$item["version_to_install"];
			if(isset($install_results[$dversion]) && $install_results[$dversion]['result'] == 'Installed')
			{
				$report['ManagedInstalls'][$key]['installed'] = TRUE;
			}
		}
	}
}

// Move removal results over to their removal items.
$removal_results = array();
if(isset($report['RemovalResults']))
{
	foreach($report['RemovalResults'] as $result)
	{
		if(is_string($result) && preg_match('/^Removal of (.+): (.+)$/', $result, $matches))
		{
			$removal_results[$matches[1]]['result'] = $matches[2] == 'SUCCESSFUL' ? 'Removed' : $matches[2];
		}
	}
}
if(isset($report['ItemsToRemove']))
{
	foreach($report['ItemsToRemove'] as $key => &$item)
	{
		$item['install_result'] = 'Pending';
		$dversion = $report['ItemsToRemove'][$key]["name"];
		if(isset($removal_results[$dversion]))
		{
			$item['install_result'] = $removal_results[$dversion]['result'];
		}
	}
	unset($item);
}
?>

<?php $package_tables = array(	'Apple Updates' =>'AppleUpdates',
							'Active Installs' => 'ItemsToInstall',
							'Active Removals' => 'ItemsToRemove',
							'Problem Installs' => 'ProblemInstalls'); ?>

<!--! Package tables -->
<?php foreach($package_tables AS $title => $report_key): ?>
	<div class="col-lg-6">
		  <h2><?php echo $title; ?></h2>
		  
			<?php if(isset($report[$report_key]) && $report[$report_key]): ?>
			<table class="table table-striped">
		      <thead>
		        <tr>
		          <th>Name</th>
		          <th>Size</th>
		          <th>Status</th>
		        </tr>
		      </thead>
		      <tbody>
				<?php foreach($report[$report_key] AS $item): ?>
		        <tr>
		          <td>
					<?php echo isset($item['display_name']) && $item['display_name'] ? $item['display_name'] : $item['name']; ?>
					<?php echo isset($item['version_to_install']) ? $item['version_to_install'] : ''; ?>
					<?php echo isset($item['installed_version']) ? $item['installed_version'] : ''; ?>
		          </td>
		          <td class="filesize" style="text-align: left;"><?php echo isset($item['installed_size']) ? $item['installed_size'] * 1024: '?'; ?></td>
		          <td><?php echo isset($item['install_result']) ? $item['install_result'] : (isset($item['installed']) && $item['installed'] ? 'installed' : "not installed"); ?></td>
		        </tr>
				<?php endforeach; ?>
		      </tbody>
		    </table>
		    <?php else: ?>
		      <p><i>No <?php echo strtolower($title); ?></i></p>
			<?php endif ?>
	</div><!-- </div class="col-lg-6"> -->
<?php endforeach; ?>

  </div><!-- </div class="row"> -->
  
  <div class="row">

<?php $package_tables = array(	'Managed Installs' =>'ManagedInstalls'); ?>

	<div class="col-lg-6">
		<?php foreach($package_tables AS $title => $report_key): ?>
		  <h2><?php echo $title; ?></h2>

			<?php if(isset($report[$report_key]) && $report[$report_key]): ?>
			<table class="table table-striped <?php echo $report_key; ?>">
		      <thead>
		        <tr>
		          <th>Name</th>
		          <th>Size</th>
		          <th>Status</th>
		        </tr>
		      </thead>
		      <tbody>
				<?php foreach($report[$report_key] AS $item): ?>
		        <tr>
		          <td>
					<?php echo isset($item['display_name']) ? $item['display_name'] : $item['name']; ?>
					<?php echo isset($item['version_to_install']) ? $item['version_to_install'] : ''; ?>
					<?php echo isset($item['installed_version']) ? $item['installed_version'] : ''; ?>
		          </td>
		          <td style="text-align: left;"><?php echo isset($item['installed_size']) ? $item['installed_size'] * 1024: 0; ?></td>
		          <td><?php echo $item['installed'] ? 'installed' : "not installed"; ?></td>
		        </tr>
				<?php endforeach; ?>
		      </tbody>
		    </table>
		    <?php else: ?>
		      <p><i>No <?php echo strtolower($title); ?></i></p>
			<?php endif; ?>
		<?php endforeach; ?>
    </div><!-- </div class="col-lg-6"> -->

    <div class="col-lg-6">
    
		<?php if(isset($report['managed_uninstalls_list'])): ?>
		  <h2>Managed Uninstalls</h2>

		  <table class="table table-striped">
		    <thead>
		      <tr>
		        <th>Name</th>
		      </tr>
		    </thead>
		    <tbody>
			<?php foreach($report['managed_uninstalls_list'] AS $item): ?>
		      <tr>
		        <td>
		          <?php echo $item; ?>
		        </td>
		      </tr>
			<?php endforeach; ?>
		    </tbody>
		  </table>
		<?php endif; ?>

    </div><!-- </div class="col-lg-6"> -->

  </div><!-- </div class="row"> -->

<pre><?php //print_r($client->rs) ?></pre>

<script>
$(document).on('appReady', function(e, lang) {

	// Format filesize
	$('td.filesize').each(function(index, el){
		var size = $(el).html();
		if(size != '?'){
			$(el).html(fileSize(size))
		}
	});

	// Initialize datatables
	$('.ManagedInstalls').dataTable({
	    serverSide: false,
	    order: [0,'asc'],
	    createdRow: function( nRow, aData, iDataIndex ) {
	    	// Update name in first column to link
	    	var size=$('td:eq(1)', nRow).html();
	        $('td:eq(1)', nRow).html(fileSize(size, 1));

	    }
	});
});
</script>