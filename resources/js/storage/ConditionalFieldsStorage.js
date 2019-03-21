export default {
    fetchAvailableResources(resource, field, conditional, value) {
        return Nova.request().post(`/nova-vendor/nova-conditional-fields/${resource}/conditional/${field}/${conditional}`,
            {
                value: value
            }
        );
    }
}
