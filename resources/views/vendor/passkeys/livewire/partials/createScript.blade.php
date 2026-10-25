@script
<script>
    Livewire.on('passkeyPropertiesValidated', async function (eventData) {
        const passkeyOptions = eventData[0].passkeyOptions;

        // Cancelled or failed in the browser, e.g. the device prompt was closed
        let passkey;
        try {
            passkey = await startRegistration({ optionsJSON: passkeyOptions });
        } catch (error) {
            window.dispatchEvent(new CustomEvent('passkey-failed', { detail: { message: 'The passkey was not created. Try again.' } }));
            return;
        }

        $wire.call('storePasskey', JSON.stringify(passkey));
    });
</script>
@endscript
