Bolt Extension Starter
======================

A starter skeleton for a Bolt v3.x Extension

To get going run the following command, replacing the last argument with the name of your extension:

`composer create-project --no-install 'bolt/bolt-extension-starter:^3.0' <newextname>`  

For more information, see this page in the Bolt documentation: https://docs.bolt.cm/extensions/building-starter/about

<h1>Infinite scroll - Bolt extension</h1>

<h2>Usage:</h2>
<ul>
<li>add {{ infiniteScroll() }} after records listing</li>
<li>set listing_records and recordsperpage to config of your content type. Example:<br>
    listing_records: 6<br>
    sort: -datepublish<br>
    recordsperpage: 6<br>
</li>
<li>To override default listing template add infinitescroll_template to config of your content type. Example:<br>
    infinitescroll_template: your-template.html.twig<br>
</li>
</ul>