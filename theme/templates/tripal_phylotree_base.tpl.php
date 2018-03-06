<?php
$my_path = path_to_theme();
if(empty($my_path)) {
  // on lis-dev, path_to_theme() is returning empty string. this is a
  // problem on lis-stage too, probably all the lis servers. Would be
  // good to figure this out, as the recent rename of the git repos to
  // tripal_phylogeny broke this once, and in general we can't really
  // know where it will be installed. workaround: hardcode the path to
  // the theme.
  $my_path = 'sites/all/modules/tripal/tripal_phylogeny';
  // note: there is no leading '/' because that is the format used by
  // path_to_theme(), even though this is effectively an absolute url.
}

$phylotree = $variables['node']->phylotree;

drupal_add_css(
    '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
    array('type' => 'external', 'group' => CSS_THEME)
);
drupal_add_css(
    '//cdnjs.cloudflare.com/ajax/libs/nvd3/1.8.4/nv.d3.min.css',
    array('type' => 'external', 'group' => CSS_THEME)
);
drupal_add_css(
    $my_path . '/theme/css/teaser-fix.css',
    array('type' => 'file', 'group' => CSS_THEME)
);

?>

<script>
<?php
// write js var with gene family name
printf("var FAMILY_NAME = '%s';\n", $phylotree->name);

// write js var with path to our theme, for use below by javascript functions.
// prefix path to theme with / because drupal returns it as a relative URL.
printf("var THEME_PATH = '/%s';\n", $my_path);


// write the tree data into the template as js var (saving one ajax
// get for json)
// if the tree has been provided as post data, write that instead.
// in that case there should also be an url provided to get the MSA.
if (!isset($_POST["json"]))
{
	// write js var having URL of json and gff data sources
	printf("var API = {
	  tree: \"/chado_phylotree/%s/json\",
	  msa: \"/lis_gene_families/chado/msa/%s-consensus/download/\"
	};\n",
		   $phylotree->phylotree_id,
		   $phylotree->name
	);

	printf("var treeData = %s;\n",
        json_encode(phylotree_by_id($phylotree->phylotree_id))
	);
}
else
{
	// write js var having URL of json and gff data sources
	printf("var API = {
	  tree: \"/chado_phylotree/%s/json\",
	  msa: \"%s\"
	};\n",
		   $phylotree->phylotree_id,
		   $_POST["msa"]
	);

    printf("var treeData = %s;;\n",$_POST["json"]);
}


?>
</script>

<div class="tripal_phylotree-data-block-desc tripal-data-block-desc">
  <p><b><?php print $phylotree->name ?></b>:
    <span id="phylotree-comment">
<?php
if( ! empty($phylotree->comment) ) {
  print $phylotree->comment;
}
?>
    </span>
  </p>
</div>

<div id="ahrd-dialog" style="display:none; font-size: 0.8rem">

<a href="https://github.com/groupschoof/AHRD"
   class="ext" tabindex="-1">AHRD's<span class="ext"></span></a>
quality-code consists of a four character string, where each
character is either &quot;<strong>*</strong>&quot; if the respective
criteria is met or &quot;<strong>-</strong>&quot; otherwise. Their
meaning is explained in the following table:

<table><tbody>
<tr>
<th> Position </th>
<th> Criteria </th>
</tr>
<tr>
<td> 1 </td>
<td> Bit score of the blast result is &gt;50 and e-value is &lt;e-10 </td>
</tr>
<tr>
<td> 2 </td>
<td> Overlap of the blast result is &gt;60% </td>
</tr>
<tr>
<td> 3 </td>
<td> Top token score of assigned HRD is &gt;0.5 </td>
</tr>
<tr>
<td> 4 </td>
<td> Gene ontology terms found in description line </td>
</tr>
</tbody></table>

</div>

<div id="ajax-spinner">
    <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
    <span class="sr-only">Loading...</span>
</div>

<div id="au-content" aurelia-app="main">
</div>

<?php
/*
 * this template depends on a few javascript libraries, but we are not
 * putting it into tripal_phylotree.info scripts[] because that
 * results in the script getting loaded *on every drupal request*
 * across the site, which is waste of resources.
 */

//
// library group/level of scripts
//
$js_config = array('type' => 'external', 'group' => JS_LIBRARY);
drupal_add_js(
    '//cdn.bio.sh/msa/1.0/msa.min.gz.js',
    $js_config
);
drupal_add_js(
    '//cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js',
    $js_config
);
drupal_add_js(
    '//cdnjs.cloudflare.com/ajax/libs/nvd3/1.8.4/nv.d3.min.js',
    $js_config
);
drupal_add_js(
    '//cdnjs.cloudflare.com/ajax/libs/lodash.js/4.16.4/lodash.min.js',
    $js_config
);
drupal_add_js(
    '//cdnjs.cloudflare.com/ajax/libs/chroma-js/1.2.1/chroma.min.js',
    $js_config
);
drupal_add_js(
    '//cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.5.15/clipboard.min.js',
    $js_config
);

drupal_add_library('system', 'ui.dialog');

//
// default group/level javascripts (loads after library group)
//
$js_dir = '/'. $my_path . '/theme/js';
drupal_add_js(
    $js_dir . '/tour-autolauncher.js',
    array('type' => 'file', 'group' => JS_DEFAULT)
);
drupal_add_js(
    $js_dir . '/interpro-linkout.js',
    array('type' => 'file', 'group' => JS_DEFAULT)
);
drupal_add_js(
    $js_dir . '/geneontology-linkout.js',
    array('type' => 'file', 'group' => JS_DEFAULT)
);
drupal_add_js(
    $js_dir . '/ahrd-descriptor.js',
    array('type' => 'file', 'group' => JS_DEFAULT)
);

// finally, use a regular script tag to inject the aurelia boostrapper
// it will populate the aurelia-app div, above.
printf('<script src="%s/aurelia/scripts/vendor-bundle.js" data-main="aurelia-bootstrapper"></script>',
       $js_dir)

?>

