<template>
    <div v-if="dependenciesSatisfied">
        <div v-for="childField in field.fields">
            <component
                :is="'form-' + childField.component"
                :errors="errors"
                :resource-id="resourceId"
                :resource-name="resourceName"
                :field="childField"
                :ref="'field-' + childField.attribute"
            />
        </div>
    </div>
</template>

<script>
import storage from '../storage/ConditionalFieldsStorage.js'
import {FormField, HandlesValidationErrors} from 'laravel-nova'

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    mounted() {
        this.checkDependency(this.$root);
    },

    data() {
        return {
            dependencyValues: {},
            dependenciesSatisfied: false,
        }
    },

    methods: {
        checkDependency(root) {
            root.$children.forEach(component => {
                if (this.componentIsDependency(component)) {
                    if (component.hasOwnProperty('value')) {
                        this.registerDependencyWatchers(component, 'value');
                    } else if (component.hasOwnProperty('selectedResource')) {
                        this.registerDependencyWatchers(component, 'selectedResource');
                    }
                }

                this.checkDependency(component);
            })
        },

        registerDependencyWatchers(component, propertyName) {
            component.$watch(propertyName, (value) => {
                this.dependencyValues[component.field.attribute] = value;
                this.updateDependencyStatus();
            }, {immediate: true});

            this.dependencyValues[component.field.attribute] = component.field[propertyName] || null;
        },

        componentIsDependency(component) {
            if (component.field === undefined) {
                return false;
            }
            for (let dependency of this.field.dependencies) {
                if (component.field.attribute === dependency.field) {
                    return true;
                }
            }
            return false;
        },

        async updateDependencyStatus() {
            for (let dependency of this.field.dependencies) {
                let dependencyValue;

                if (dependency.hasOwnProperty('notEmpty') && this.dependencyValues[dependency.field]) {
                    this.dependenciesSatisfied = true;
                    return;
                }

                if (dependency.hasOwnProperty('callback')) {
                    if (this.dependencyValues[dependency.field]) {
                        dependencyValue = await this.getAvailableResources(dependency.field,
                            this.dependencyValues[dependency.field].value || this.dependencyValues[dependency.field]);
                    }
                } else {
                    if (dependency.hasOwnProperty('value')) {
                        dependencyValue = dependency.value;
                    } else if (dependency.hasOwnProperty('selectedResource')) {
                        dependencyValue = dependency.selectedResource.id || dependency.selectedResource;
                    }
                }

                if (this.dependencyValues[dependency.field] === dependencyValue ||
                    (dependency.hasOwnProperty('callback') && (dependencyValue && dependencyValue.data))) {
                    this.dependenciesSatisfied = true;
                    return;
                }
            }

            this.dependenciesSatisfied = false;
        },

        getAvailableResources(conditional, value) {
            if (conditional && value) {
                return storage
                    .fetchAvailableResources(this.resourceName, this.fieldAttribute, conditional, value);
            }

            return null;
        },

        fill(formData) {
            if (this.dependenciesSatisfied) {
                _.each(this.field.fields, field => {
                    field.fill(formData);
                })
            }
        },
    },
}
</script>
