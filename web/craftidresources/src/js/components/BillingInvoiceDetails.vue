<template>
	<div>
		<div class="flex">
			<div class="flex-1">
				<h4>Invoice details</h4>

				<dl v-if="!showForm">
					<dt>VAT ID</dt>
					<dd>
						<template v-if="billingAddress && billingAddress.businessTaxId">
							{{ billingAddress.businessTaxId }}
						</template>
						<template v-else>
							<span class="text-secondary">VAT ID not defined.</span>
						</template>
					</dd>
				</dl>
			</div>

			<div v-if="!showForm">
				<button @click="editInvoiceDetails()" type="button"
						class="btn btn-secondary btn-sm"
						data-facebox="#billing-contact-info-modal">
					<i class="fas fa-pencil-alt"></i>
					Edit
				</button>
			</div>
		</div>

		<form v-if="showForm" @submit.prevent="save()">
			<text-field id="businessTaxId" label="Tax ID" v-model="invoiceDetailsDraft.businessTaxId" :errors="errors.businessTaxId" />
			<input type="submit" class="btn btn-primary" value="Save" />
			<input type="button" class="btn btn-secondary" value="Cancel" @click="cancel()" />
		</form>
	</div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import TextField from './fields/TextField'

    export default {

        components: {
            TextField,
        },

        data() {
            return {
                errors: {},
                showForm: false,
                invoiceDetailsDraft: {},
            }
        },

        computed: {

            ...mapGetters({
                currentUser: 'currentUser',
                billingAddress: 'billingAddress',
            }),

        },

        methods: {

            /**
             * Edit invoice details.
             */
            editInvoiceDetails() {
                this.showForm = true;
                this.invoiceDetailsDraft = JSON.parse(JSON.stringify(this.billingAddress));
            },

            /**
             * Saves the user’s invoice details.
             */
            save() {
                let data = {
                    businessTaxId: this.invoiceDetailsDraft.businessTaxId,
                }

                if(this.billingAddress) {
                    data = Object.assign({}, data, {
                        id: this.billingAddress.id,
                        firstName: this.billingAddress.firstName,
                        lastName: this.billingAddress.lastName,
                        businessName: this.billingAddress.businessName,
                        address1: this.billingAddress.address1,
                        address2: this.billingAddress.address2,
                        city: this.billingAddress.city,
                        state: this.billingAddress.state,
                        zipCode: this.billingAddress.zipCode,
                        country: this.billingAddress.country,
                    });
				}

                this.$store.dispatch('saveBillingInfo', data).then(response => {
                    this.$root.displayNotice('Invoice details saved.');
                    this.showForm = false;
                    this.errors = {};
                }).catch(response => {
                    const errorMessage = response.data && response.data.error ? response.data.error : 'Couldn’t save invoice details.';
                    this.$root.displayError(errorMessage);

                    this.errors = response.data && response.data.errors ? response.data.errors : {};
                });
            },

            /**
             * Cancel changes.
             */
            cancel() {
                this.showForm = false;
                this.errors = {};
            }

        }

    }
</script>
