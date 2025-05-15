(function () {
    const { addFilter } = wp.hooks;

    addFilter(
        'woocommerce.blocks.checkout.fields',
        'myplugin/modify-state-names',
        (checkoutFields) => {
            if (
                checkoutFields?.billing?.state &&
                checkoutFields.billing.state.options
            ) {
                const modifiedOptions = {};
                Object.entries(checkoutFields.billing.state.options).forEach(
                    ([abbr, label]) => {
                        modifiedOptions[abbr] = `${label} (${abbr})`;
                    }
                );
                checkoutFields.billing.state.options = modifiedOptions;
            }

            return checkoutFields;
        }
    );
})();
