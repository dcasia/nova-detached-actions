<template>

    <div class="flex w-full justify-end items-center mx-3">

        <button
            data-testid="import-action-confirm"
            dusk="run-import-action-button"
            @click.prevent="openConfirmationModal(action)"
            class="btn btn-default btn-detached-action"
            :title="action.label"
            v-for="action in detachedActions"
            :key="action.uriKey">

            <span>{{ __(action.label) }}</span>

        </button>

        <transition name="fade">

            <component
                :is="selectedAction.component"
                :working="working"
                v-if="confirmActionModalOpened"
                :selected-resources="selectedResources"
                :resource-name="resourceName"
                :action="selectedAction"
                :errors="errors"
                @confirm="executeAction"
                @close="confirmActionModalOpened = false"/>

        </transition>

    </div>

</template>

<script>

    import _ from 'lodash'
    import { InteractsWithResourceInformation } from 'laravel-nova'
    import HandlesActions from '~~nova~~/mixins/HandlesActions'

    export default {
        mixins: [ HandlesActions, InteractsWithResourceInformation ],

        data: () => ( {
            actionsList: [],
            selectedResources: 'all'
        } ),

        /**
         * Mount the component and retrieve its initial data.
         */
        async created() {
            this.getActions()
        },

        methods: {
            /**
             * Get the actions available for the current resource.
             */
            getActions() {
                this.actionsList = []
                this.pivotActions = null
                return Nova.request()
                    .get(`/nova-api/${ this.resourceName }/actions`, {
                        params: {
                            isDetachedAction: true,
                            viaResource: this.viaResource,
                            viaResourceId: this.viaResourceId,
                            viaRelationship: this.viaRelationship,
                            relationshipType: this.relationshipType
                        }
                    })
                    .then(response => {
                        this.actionsList = _.filter(response.data.actions, action => !action.onlyOnDetail)
                        this.pivotActions = response.data.pivotActions
                    })
            },

            /**
             * Confirm with the user that they actually want to run the selected action.
             */
            openConfirmationModal(action) {
                this.selectedActionKey = action.uriKey
                this.confirmActionModalOpened = true
            }

        },

        computed: {
            /**
             * Get the query string for an action request.
             */
            actionRequestQueryString() {
                return {
                    isDetachedAction: true,
                    action: this.selectedActionKey,
                    pivotAction: this.selectedActionIsPivotAction,
                    search: this.queryString.currentSearch,
                    filters: this.queryString.encodedFilters,
                    trashed: this.queryString.currentTrashed,
                    viaResource: this.queryString.viaResource,
                    viaResourceId: this.queryString.viaResourceId,
                    viaRelationship: this.queryString.viaRelationship
                }
            },
            detachedActions() {
                return _.filter(this.allActions, a => a.detachedAction || false)
            },
            /**
             * Get all of the available actions.
             */
            allActions() {
                return this.actionsList
            }
        }
    }

</script>

<style lang="scss" scoped>

    .btn-detached-action {

        margin-left: .75rem;
        color: #fff;
        background-color: var(--primary);
        text-overflow: ellipsis;
        overflow: hidden;

        &:hover {
            background-color: var(--primary-dark);
        }

    }

</style>
