<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-overlay :absolute="absolute" :value="overlay">
        <v-progress-circular
          :size="70"
          :width="7"
          color="primary"
          indeterminate
        ></v-progress-circular>
      </v-overlay>
      <v-main>
        <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>
        <v-card>
          <v-card-title class="mb-0 pb-0">
            <span class="headline">Create User</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="ml-4">
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <template v-for="(field, i) in oinv_fields">
                  <v-text-field
                    name="name"
                    v-model="oinv_fields[i][field.field_name]"
                    :label="field.description"
                    @change="modelChange(oinv_fields[i][field.field_name])"
                  ></v-text-field>

                  {{ field }}
                </template>
                
                
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider class="mb-3 mt-4"></v-divider>
          <v-card-actions class="pa-0">
            <v-btn
              color="primary"
              @click="save"
              :disabled="disabled"
              class="ml-6 mb-4 mr-1"
            >
              Save
            </v-btn>
            <v-btn color="#E0E0E0" to="/" class="mb-4"> Cancel </v-btn>
          </v-card-actions>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<script>

import axios from "axios";
import { validationMixin } from "vuelidate";
import {
  required,
  maxLength,
  email,
  minLength,
  sameAs,
} from "vuelidate/lib/validators";

export default {

  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
      email: { required, email },
      password: { required, minLength: minLength(8) },
      confirm_password: { required, sameAsPassword: sameAs("password") },
    },
  },
  data() {
    return {
      absolute: true,
      overlay: false,
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/dashboard",
        },
        {
          text: "Create User",
          disabled: true,
        },
      ],

      disabled: false,
      oinv_fields: [],
      inv1_fields: [],
      editedIndex: -1,
      editedItem: {
        
      },
      defaultItem: {
        
      },


    };
  },

  methods: {
    getARInvoiceFields() {
      axios.get("/api/sap/ar_invoice").then(
        (response) => {

          let oinv_fields = response.data.sap_oinv_table.sap_table_fields;
          let inv1_fields = response.data.sap_inv1_table.sap_table_fields;
      
          let fields = [];
          oinv_fields.forEach((value, index) => {
            console.log(value.field_name);
            this.oinv_fields.push({
              [value.field_name]: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
            });

          });

          inv1_fields.forEach((value, index) => {
            this.inv1_fields.push({
              [value.field_name]: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
            })
          });

          console.log(this.fields);
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    showAlert() {
      this.$swal({
        position: "center",
        icon: "success",
        title: "Record has been saved",
        showConfirmButton: false,
        timer: 2500,
      });
    },

    save() {
      this.$v.$touch();
      this.userError = {
        name: [],
        email: [],
        password: [],
        confirm_password: [],
      };

      if (!this.$v.$error) {
        this.disabled = true;
        this.overlay = true;

        const data = this.editedItem;

        axios.post("/api/user/store", data).then(
          (response) => {
            if (response.data.success) {
              // send data to Sockot.IO Server
              // this.$socket.emit("sendData", { action: "user-create" });

              this.showAlert();
              this.clear();

            }
            else {
              let errors = response.data;
            }
            this.overlay = false;
            this.disabled = false;
          },
          (error) => {
            this.isUnauthorized(error);

            this.overlay = false;
            this.disabled = false;
          }
        );
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
      
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },
    modelChange(data){
      console.log(data);
    }
  },
  computed: {
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] = "Bearer " + localStorage.getItem("access_token");
    this.getARInvoiceFields();
  },
};
</script>