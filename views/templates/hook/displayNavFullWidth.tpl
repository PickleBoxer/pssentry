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
<!-- Sentry Integrations Module -->
<div id="browser-alert" style="display:none;">
  <div class="alert alert-danger" role="alert">
    <h4 class="alert-heading">
      <i class="material-icons">warning</i> Please update your browser!
    </h4>
    <p>You are using an outdated browser. Unfortunately, this website might not work as expected.</p>
    <p>We recommend you to update your browser or to use another application.</p>
  </div>
</div>
{literal}
<script type="application/javascript">
  var str = 'class ಠ_ಠ extends Array {constructor(j = "a", ...c) {const q = (({u: e}) => {return { [`s${c}`]: Symbol(j) };})({});super(j, q, ...c);}}' +
    'new Promise((f) => {const a = function* (){return "\\{u20BB7}".match(/./u)[0].length === 2 || true;};for (let vre of a()) {' +
    'const [uw, as, he, re] = [new Set(), new WeakSet(), new Map(), new WeakMap()];break;}f(new Proxy({}, {get: (han, h) => h in han ? han[h] ' +
    ': "42".repeat(0o10)}));}).then(bi => new ಠ_ಠ(bi.rd));';
  try {
    eval(str);
  } catch(e) {
    alert("You are using an outdated browser. Unfortunately, this website might not work as expected.")
    document.getElementById('browser-alert').setAttribute('class', 'd-block')
    Sentry.captureMessage('Browser not supported')
    Sentry.close()
  }
</script>
{/literal}
<!-- END Sentry Integrations Module -->