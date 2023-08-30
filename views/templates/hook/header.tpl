<!-- Sentry Integrations Module -->
{$sentry_loader_script nofilter}
<script>
  Sentry.onLoad(function() {
    Sentry.init({
      debug: {if $sentry_debug}true{else}false{/if},
      tracePropagationTargets: [
        "{$urls.base_url}",
        //"https://.*.otherservice.org/.*",
      ],
    });
    Sentry.setUser({
      id: '{$customer.id}',
      email: '{$customer.email}'
    });
    // etc.
  });
</script>
<!-- /Sentry Integrations Module -->