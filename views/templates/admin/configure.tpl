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
	<h3><i class="icon icon-credit-card"></i> {l s='Sentry Integrations' mod='pssentry'}</h3>
	<p>
		<strong>{l s='Here is my new generic module!' mod='pssentry'}</strong><br />
		{l s='Thanks to PrestaShop, now I have a great module.' mod='pssentry'}<br />
		{l s='I can configure it using the following configuration form.' mod='pssentry'}
	</p>
	<br />
	<p>
		{l s='This module will boost your sales!' mod='pssentry'}
	</p>

	<small>$ php bin/console debug:config sentry</small><br />
	<small>$ php bin/console debug:config monolog</small><br />
	<small>$ php bin/console sentry:test</small><br />

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
				$('#command-output').append('<div style="white-space: pre-wrap;">' + event.data + '</div>');
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
	});
</script>