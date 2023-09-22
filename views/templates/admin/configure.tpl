{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 *}
{literal}
	<style>
		.spinner-border {
			display: inline-block;
			width: 2rem;
			height: 2rem;
			vertical-align: text-bottom;
			border: 0.25em solid currentColor;
			border-right-color: transparent;
			border-radius: 50%;
			-webkit-animation: spinner-border .75s linear infinite;
			animation: spinner-border .75s linear infinite;
		}

		.spinner-border-sm {
			width: 1rem;
			height: 1rem;
			border-width: 0.2em;
		}

		@keyframes spinner-border {
			to {
				transform: rotate(360deg);
			}
		}
	</style>
{/literal}
<div class="panel">
	<button type="button" class="btn btn-secondary btn-sm">
		Symfony <span class="badge badge-success">{$symfony}</span>
	</button>
	<button type="button" class="btn btn-secondary btn-sm">
		Monolog Bundle <span class="badge badge-success">{$monolog_bundle}</span>
	</button>
</div>
<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Sentry Integrations' mod='pssentry'}</h3>
	<p>
		<strong>{l s='Module thats simplify the Sentry integration' mod='pssentry'}</strong><br />
		{l s='Your PrestaShop store can now benefit from the Sentry monitoring integration with the PSSentry module. This module allows you to easily configure Sentry to monitor your store and receive notifications when errors occur.' mod='pssentry'}<br />
	</p>
	<br />
	<p>
		{l s='The configuration form is user-friendly and provides information about the Symfony and Monolog bundles used by PrestaShop. You can also run tests for Sentry and Monolog to ensure that everything is working properly.' mod='pssentry'}
	</p>
	<br />
	<p>
		{l s='Boost your sales and keep your store running smoothly with the PSSentry module!' mod='pssentry'}
	</p>

	<button id="run-sentry-test" class="btn btn-primary" type="button" data-command="sentry:test">
		Run Sentry Test
		<span id="loading-indicator-sentry-test" class="spinner-border spinner-border-sm"
			style="display: none;margin-left:.5rem;" role="status" aria-hidden="true"></span>
	</button>

	<button id="run-monolog-debug" class="btn btn-primary" type="button" data-command="debug:config" data-arg="monolog">
		Run Monolog Debug
		<span id="loading-indicator-monolog-debug" class="spinner-border spinner-border-sm"
			style="display: none;margin-left:.5rem;" role="status" aria-hidden="true"></span>
	</button>

	<button id="run-sentry-debug" class="btn btn-primary" type="button" data-command="debug:config" data-arg="sentry">
		Run Sentry Debug
		<span id="loading-indicator-sentry-debug" class="spinner-border spinner-border-sm"
			style="display: none;margin-left:.5rem;" role="status" aria-hidden="true"></span>
	</button>

	<button id="clear-cache-btn">Clear Cache</button>

	<div id="command-output"></div>
</div>

{* <div class="panel">
	<h3><i class="icon icon-tags"></i> {l s='Documentation' mod='pssentry'}</h3>
	<p>
		&raquo; {l s='You can get a PDF documentation to configure this module' mod='pssentry'} :
		<ul>
			<li><a href="#" target="_blank">{l s='English' mod='pssentry'}</a></li>
			<li><a href="#" target="_blank">{l s='French' mod='pssentry'}</a></li>
		</ul>
	</p>
</div> *}

<script>
	$(document).ready(function() {
		$('#run-sentry-test, #run-monolog-debug, #run-sentry-debug').click(function() {
			var button = $(this); // Store a reference to the clicked button

			var command = $(this).data('command');
			var arg = $(this).data('arg');

			// Clear the #command-output element
			$('#command-output').empty();

			var source = new EventSource('{$controller_link}&ajax=1&action=RunSymfonyCommand&command=' + command + (arg ? '&arg=' + arg : ''));

			button.prop('disabled', true);
			button.find('.spinner-border').show();


			source.addEventListener('message', function(event) {
				console.log(event.data);
				// Add code here to display the event data in the front-end
				$('#command-output').append('<div style="white-space: pre-wrap;">' + event.data +
					'</div>');
			});

			source.addEventListener('end', function(event) {
				console.log(event.data);
				// Add code here to display the event data in the front-end
				$('#command-output').append('<div>' + event.data + '</div>');
				source.close();
				button.prop('disabled', false);
				button.find('.spinner-border').hide();
			});

			source.addEventListener('error', function(event) {
				console.error('An error occurred: ' + event.data);
				source.close();
				button.prop('disabled', false);
				button.find('.spinner-border').hide();
			});
		});
		$('#clear-cache-btn').click(function() {
        $.ajax({
            url: '{$controller_link}',
            type: 'POST',
            data: {
                ajax: true,
                action: 'ClearSymfonyCache'
            },
            success: function(response) {
                // Handle success response here
                console.log(response);
            },
            error: function(xhr, status, error) {
                // Handle error response here
                console.log(error);
            }
        });
    });
	});
</script>