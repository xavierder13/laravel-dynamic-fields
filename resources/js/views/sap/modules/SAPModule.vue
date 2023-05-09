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
              <template v-for="(field, i) in parent_table_fields">
                <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                  <v-text-field
                    name="name"
                    v-model="parent_table_fields[i][field.field_name]"
                    :label="field.description"
                    @input="modelChange(parent_table_fields[i][field.field_name])"
                  ></v-text-field>
                </v-col>
              </template>
            </v-row>
            <v-row>
              <v-col>
                <v-card>
                  <v-card-text>
                    <v-tabs v-model="tab">
                      <v-tab v-for="(child, i) in child_tables" :key="child.table_name">
                        {{ child.description }}
                      </v-tab>
                    </v-tabs>
                    <v-tabs-items v-model="tab">
                      <v-tab-item v-for="(child, i) in child_tables" :key="child.table_name">
                        <v-simple-table class="elevation-1" >
                          <template v-slot:default>
                            <thead>
                              <tr>
                                <th class="pa-2" width="10px">#</th>
                                <th class="pa-2" v-for="(field, j) in child_table_fields[child.table_name].fields" :key="j"> 
                                  {{ field.description }}
                                </th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(item, j) in child_table_fields[child.table_name].data" :key="j">
                                <td> {{ j+1 }} </td>
                                <td v-for="(item, j) in child_table_fields[child.table_name].fields" :key="j" >asdad</td>
                              </tr>
                              <tr>
                                <td>
                                  
                                  <!-- <template v-if="index === editedFieldIndex || item.status === 'New'">
                                    <td class="pa-2">
                                      <v-text-field
                                        name="field_name"
                                        v-model="editedField.field_name"
                                        dense
                                        hide-details
                                        :error-messages="fieldNameErrors"
                                        @input="$v.editedField.field_name.$touch() + (errorFields.field_name = '')"
                                        @blur="$v.editedField.field_name.$touch()"
                                      ></v-text-field>
                                      
                                    </td>
                                    <td class="pa-2">
                                      <v-text-field
                                        name="description"
                                        v-model="editedField.description"
                                        dense
                                        hide-details
                                        :error-messages="fieldDescriptionErrors"
                                        @input="$v.editedField.description.$touch()"
                                        @blur="$v.editedField.description.$touch()"
                                      ></v-text-field>
                                    </td>
                                    <td class="pa-2">
                                      <v-autocomplete
                                        name="type"
                                        v-model="editedField.type"
                                        :items="data_types"
                                        item-text="type"
                                        item-value="value"
                                        dense
                                        hide-details
                                        :error-messages="fieldTypeErrors" 
                                        @input="$v.editedField.type.$touch()"
                                        @blur="$v.editedField.type.$touch()"
                                      ></v-autocomplete>
                                    </td>
                                    <td class="pa-2">
                                      <v-text-field-integer
                                        class="pa-0"
                                        v-model="editedField.length"
                                        v-bind:properties="{
                                          name: 'length',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                          error: $v.editedField.length.$error,
                                          messages: fieldLengthErrors,
                                          disabled: editedField.type !== 'string'
                                        }"
                                        @input="$v.editedField.length.$touch()"
                                        @blur="$v.editedField.length.$touch()"
                                        
                                      >
                                      </v-text-field-integer>
                                    </td>
                                    <td class="pa-2">
                                      <v-text-field
                                        name="default_value"
                                        v-model="editedField.default_value"
                                        dense
                                        hide-details
                                        v-if="editedField.type === 'string' || editedField.type === ''"
                                      ></v-text-field>
                                      <v-menu
                                        ref="menu"
                                        class="pa-0"
                                        v-model="date_menu_default_value"
                                        :close-on-content-click="false"
                                        transition="scale-transition"
                                        offset-y
                                        min-width="auto"
                                        v-if="editedField.type === 'date'"
                                      >
                                        <template v-slot:activator="{ on, attrs }">
                                          <v-text-field
                                            class="pa-0 ma-0"
                                            v-model="computedDefaultValueFormatted"
                                            prepend-icon="mdi-calendar"
                                            v-bind="attrs"
                                            v-on="on"
                                            hide-details=""
                                          ></v-text-field>
                                        </template>
                                        <v-date-picker
                                          v-model="editedField.default_value"
                                          no-title
                                          scrollable
                                          @input="date_menu_default_value = false"
                                        >
                                        </v-date-picker>
                                      </v-menu>
                                      <v-text-field-integer
                                        class="pa-0"
                                        v-model="editedField.default_value"
                                        v-bind:properties="{
                                          name: 'default_value',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                        }"
                                        v-if="editedField.type === 'integer'"
                                      >
                                      </v-text-field-integer>
                                      <v-text-field-money
                                        class="pa-0"
                                        v-model="editedField.default_value"
                                        v-bind:properties="{
                                          name: 'length',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                        }"
                                        v-bind:options="{
                                          length: 11,
                                          precision: 2,
                                          empty: null,
                                        }"
                                        v-if="editedField.type === 'decimal'"
                                      >
                                      </v-text-field-money>
                                    </td>
                                    <td>
                                      <v-checkbox
                                        name="has_options"
                                        v-model="editedField.has_options"
                                        dense
                                        hide-details
                                        @click="hasOptionsClick()"
                                      ></v-checkbox>
                                    </td>
                                    <td>
                                      <v-checkbox
                                        name="is_required"
                                        v-model="editedField.is_required"
                                        dense
                                        hide-details
                                      ></v-checkbox>
                                    </td>
                                  </template>
                              
                                  <template v-if="index !== editedFieldIndex && item.status !== 'New' ">
                                    <td class="pa-2">
                                      <v-icon
                                        small
                                        class="mr-2"
                                        color="green"
                                        @click="editField(item)"
                                        :disabled="tableFieldsMode === 'Add' ? true : false"
                                      >
                                        mdi-pencil
                                      </v-icon>

                                      <v-icon
                                        small
                                        color="red"
                                        @click="removeFieldRow(item)"
                                        :disabled="['Add', 'Edit'].includes(tableFieldsMode)"
                                      >
                                        mdi-delete
                                      </v-icon>
                                    </td>
                                  </template>
                                  
                                  <template v-if="index === editedFieldIndex ? true : false || item.status === 'New' ">
                                    <td class="pa-2">
                                      <v-btn
                                        x-small
                                        :disabled="disabled"
                                        @click="saveField()"
                                        icon
                                      >
                                        <v-icon color="primary">mdi-content-save</v-icon>
                                      </v-btn>
                                      <v-btn
                                        x-small
                                        color="#E0E0E0"
                                        @click="cancelFieldEvent(item)"
                                        icon
                                      >
                                        <v-icon color="red">mdi-cancel</v-icon>
                                      </v-btn>
                                    </td>
                                  </template> -->
                                 
                                </td>
                              </tr>
                            </tbody>
                            <tfoot>
                              <tr>
                                <td :colspan="child_table_fields[child.table_name].length + 1" class="text-right">
                                  <v-btn class="primary" x-small @click="newRowItem(child)">add item</v-btn>
                                </td>
                              </tr>
                            </tfoot>
                          </template>
                        </v-simple-table>
                      </v-tab-item>
                    </v-tabs-items>
                  </v-card-text>
                </v-card>
                
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
      parent_table_fields: [],
      child_tables: [],
      child_table_fields: [],
      editedIndex: -1,
      editedItem: {},
      defaultItem: {},
      tab: null,

    };
  },

  methods: {
    getARInvoiceFields() {
      let sap_table_id = this.$route.params.sap_table_id;
      axios.get("/api/sap/module/"+ sap_table_id).then(
        (response) => {
          console.log(response.data);
          let parent_table_fields = response.data.parent_table.sap_table_fields;
          this.child_tables = response.data.child_tables;

          let fields = [];
          parent_table_fields.forEach((value, index) => {
            
            this.parent_table_fields.push({
              [value.field_name]: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
            });

          });

          console.log('parant_table_fields', this.parent_table_fields);

          this.child_tables.forEach((value, index) => {

            this.child_table_fields[value.table_name] = Object.assign({}, { fields: [], data: [] });

            value.sap_table_fields.forEach((val, i) => {

              this.child_table_fields[value.table_name].fields.push({
                [val.field_name]: '',
                field_name: val.field_name,
                description: val.description, 
                type: val.type,
              });

            });

          });

          let data = [
            'INV1', 
            {
              columns: [
                {
                  'mktg': '',
                  field_name: 'mktg',
                  description: 'Marketing Event', 
                  type: 'string',
                },
                {
                  'mktg2': '',
                  field_name: 'mktg2',
                  description: 'Marketing Event2', 
                  type: 'string',
                },
              ],
              data: [] 
            }

          ]

          console.log(this.child_table_fields);
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
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
    newRowItem(item)
    {
      console.log(item);
      this.child_table_fields[item.table_name].data.push('');
    },
    showAlert(msg) {
      this.$swal({
        position: "center",
        icon: "success",
        title: msg,
        showConfirmButton: false,
        timer: 2500,
      });
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
      console.log(this.parent_table_fields);
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