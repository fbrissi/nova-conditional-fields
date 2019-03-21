Nova.booting((Vue, router, store) => {
    Vue.component('detail-nova-conditional-fields', require('./components/DetailField'))
    Vue.component('form-nova-conditional-fields', require('./components/FormField'))
})
