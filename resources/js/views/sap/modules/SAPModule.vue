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
            <span class="headline">Create {{ parent_table.description }}</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text>
            <v-row>
              <template v-for="(field, i) in parent_table_fields">
                <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                  <v-text-field
                    name="name"
                    v-model="parent_table_fields[i]['value']"
                    :label="field.description"
                    @input="modelChange(parent_table_fields[i]['value'])"
                  ></v-text-field>
                </v-col>
              </template>
            </v-row>
            <v-row v-if="child_tables.length">
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
                        <v-simple-table class="elevation-1" id="child_table">
                          <template v-slot:default>
                            <thead>
                              <tr>
                                <th class="pa-2" width="10px">#</th>
                                <th class="pa-2" v-for="(field, j) in child_table_fields[child.table_name].fields" :key="j"> 
                                  {{ field.description }}
                                </th>
                                <th class="pa-2" width="80px"> Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(item, row) in child_table_fields[child.table_name].data" :key="row">
                                <td class="pa-2"> {{ row + 1 }} </td>
                                <td class="pa-2" v-for="(field, col) in child_table_fields[child.table_name].fields" :key="col" >
            
                                  <template v-if="row === editedIndex || item.status === 'New'">
                                    
                                    <!-- if Field has Options -->
                                    <template v-if="field.has_options">
                                      <v-autocomplete
                                        class="pa-0"
                                        :name="field.field_name + '[]'"
                                        :items="field.options"
                                        v-model="editedItem[child.table_name].data[col].value"
                                        item-text="description"
                                        item-value="value"
                                        required
                                        dense
                                        hide-details
                                      >
                                        <template slot="selection" slot-scope="data">
                                          {{ data.item.value + ' - ' + data.item.description }}
                                        </template>
                                        <template slot="item" slot-scope="data">
                                          {{ data.item.value + ' - ' + data.item.description }}
                                        </template>
                                      </v-autocomplete>
                                    </template>

                                    <!-- if Field no options -->
                                    <template v-if="!field.has_options">
                                      <!-- if Field Type string -->
                                      <v-text-field
                                        class="pa-0 ma-0"
                                        :name="field.field_name + '[]'"
                                        v-model="editedItem[child.table_name].data[col].value"
                                        dense
                                        hide-details
                                        v-if="field.type === 'string'"
                                      ></v-text-field>

                                      <!-- if Field Type date -->
                                      <v-menu
                                        ref="menu"
                                        class="pa-0"
                                        v-model="editedItem[child.table_name].data[col].date_menu"
                                        :close-on-content-click="false"
                                        transition="scale-transition"
                                        offset-y
                                        min-width="auto"
                                        v-if="field.type === 'date'"
                                      >
                                        <template v-slot:activator="{ on, attrs }">
                                          <v-text-field
                                            :name="field.field_name + '[]'"
                                            v-model="editedItem[child.table_name].data[col].formatted_date"
                                            class="pa-0 ma-0"
                                            prepend-icon="mdi-calendar"
                                            v-bind="attrs"
                                            v-on="on"
                                            hide-details=""
                                          ></v-text-field>
                                        </template>
                                        <v-date-picker
                                          v-model="editedItem[child.table_name].data[col].value"
                                          no-title
                                          scrollable
                                          @input="formatDateValue(child.table_name, col)"
                                        >
                                        </v-date-picker>
                                      </v-menu>

                                      <!-- if Field Type integer -->
                                      <v-text-field-integer
                                        class="pa-0"
                                        v-model="editedItem[child.table_name].data[col].value"
                                        v-bind:properties="{
                                          name: field.field_name + '[]',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                        }"
                                        v-if="field.type === 'integer'"
                                      >
                                      </v-text-field-integer>

                                      <!-- if Field Type decimal -->
                                      <v-text-field-money
                                        class="pa-0"
                                        v-model="editedItem[child.table_name].data[col].value"
                                        v-bind:properties="{
                                          name: field.field_name + '[]',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                        }"
                                        v-bind:options="{
                                          length: 11,
                                          precision: 2,
                                          empty: null,
                                        }"
                                        v-if="field.type === 'decimal'"
                                      >
                                      </v-text-field-money>
                                    </template>

                                  </template>

                                  <template v-if="row !== editedIndex && item.status !== 'New' ">
                                    <td class="pa-2"> mode
                                      <v-icon
                                        small
                                        class="mr-2"
                                        color="green"
                                        @click="editField(item)"
                                        :disabled="tableRowMode === 'Add' ? true : false"
                                      >
                                        mdi-pencil
                                      </v-icon>

                                      <v-icon
                                        small
                                        color="red"
                                        @click="removeRowdRow(item)"
                                        :disabled="['Add', 'Edit'].includes(tableRowMode)"
                                      >
                                        mdi-delete
                                      </v-icon>
                                    </td>
                                  </template>
                                </td>
                                <template v-if="row === editedIndex ? true : false || item.status === 'New' ">
                                  <td class="pa-2">
                                    <v-btn
                                      x-small
                                      :disabled="disabled"
                                      @click="saveRow()"
                                      icon
                                    >
                                      <v-icon color="primary">mdi-content-save</v-icon>
                                    </v-btn>
                                    <v-btn
                                      x-small
                                      color="#E0E0E0"
                                      @click="cancelRowEvent(item)"
                                      icon
                                    >
                                      <v-icon color="red">mdi-cancel</v-icon>
                                    </v-btn>
                                  </td>
                                </template>
                              </tr>
                            </tbody>
                            <tfoot>
                              <tr>
                                <td :colspan="child_table_fields[child.table_name].fields.length + 2" class="text-right">
                                  <v-btn class="primary" x-small @click="newRow(child, i)">add item</v-btn>
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
              @click="saveData()"
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
<style>
#child_table th, #child_table td { border:1px solid #dddddd; border-bottom:1px solid #dddddd;}
</style>
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
      parent_table: "",
      parent_table_fields: [],
      child_tables: [],
      child_table_fields: [],
      editedIndex: -1,
      editedItem: [],
      defaultItem: [],
      tab: null,
      mode: "",
      tableRowMode: "",
      rowUnsaved: false,
      date_menu: false,
      row_date_menu: [],
      formattedDateValue: [],
    };
  },

  methods: {
    getTableFields() {

      this.resetData();

      let sap_table_id = this.$route.params.sap_table_id;
      axios.get("/api/sap/module/"+ sap_table_id).then(
        (response) => {
          console.log(response.data);
          let data = response.data;
          let parent_table_fields = data.parent_table.sap_table_fields;

          this.parent_table = data.parent_table;
          this.child_tables = data.child_tables;


          parent_table_fields.forEach((value, index) => {
            
            this.parent_table_fields.push({
              value: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
            });

          });

          this.child_tables.forEach((value, index) => {

            let table_name = value.table_name;

            this.child_table_fields[table_name] = Object.assign({}, { fields: [], data: [] });
            this.editedItem[table_name] = Object.assign({}, { data: [] });
            this.row_date_menu[table_name] = [];

            value.sap_table_fields.forEach((val, i) => {

              this.child_table_fields[table_name].fields.push({
                field_name: val.field_name,
                description: val.description, 
                type: val.type,
                has_options: val.has_options,
                options: val.sap_table_field_options,
              });

              this.editedItem[table_name].data.push({
                value: '',
                field_name: val.field_name,
                description: val.description, 
                type: val.type,
                date_menu: false,
                fomatted_data: null,
              });

              this.row_date_menu[table_name].push(false);

            });

          });

        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    saveData() {

    },

    async newRow(item, tab_index)
    {
      this.tableRowMode = "Add";
      let table_name = item.table_name;
      let data = this.child_table_fields[table_name].data;

      let hasNew = false;

      data.forEach((value, index) => {
        if (value.status === "New") {
          hasNew = true;
        }
      });

      if (!hasNew) {
        await data.push({ status: "New" });
      }
      
      // get the index of latest pushed data 
      this.editedIndex =  await data.length - 1;
      
      // auto scroll down when adding an item
      // await this.updateScroll(null);

      this.tab = null; // set tab model value into null to update/refresh content
      this.tab = tab_index; // set set tab model value into current index

    },

    saveRow(item) {
      let data = this.child_table_fields[item.table_name].data;
  
      let index = data.indexOf({ status: 'New' }); 
      data.splice(index, 1);
      data.push(this.editedItem);
    },

    storeRow() { 
      this.disabled = true;
      const data = Object.assign(this.editedItem, { 
        sap_table_id: this.editedItem.id,
        sap_table_field_options: this.sap_table_field_options, 
      });

      axios.post('/api/sap/udf/store_field', data).then(
        (response) => {
          this.disabled = false;
          let data = response.data;
          console.log(data);
          if(data.success)
          {
            let index = this.sap_table_fields.indexOf({ status: 'New' }); 
            this.sap_table_fields.splice(index, 1);

            // this.editedItem = Object.assign(this.editedItem, { sap_table_field_options: this.sap_table_field_options})
            this.sap_table_fields.push(data.sap_table_field);
            this.sap_tables[this.editedIndex] = this.sap_table_fields;

            this.showAlert(data.success);
            this.resetFieldData();
          }
          else//if return object is table_name then get the error
          { 
            let object_name = Object.keys(data)[0];
            this.errorFields[object_name] = data.[object_name][0];
          }

        },
        (error) => {
          this.isUnauthorized(error);
          this.disabled = false;
          this.loading = false;
        }
      );
      
    },

    updateRow() {
      const data = Object.assign(this.editedItem, { sap_tabe_field_options: this.sap_tabe_field_options })
      this.loading = true;
      this.disabled = true;
      axios.post('/api/sap/udf/update_field/'+this.editedItem.id, this.editedItem).then(
        (response) => {
          this.disabled = false;
          console.log(response.data);
          let data = response.data;

          if(data.success)
          { 
            this.sap_table_fields[this.editedIndex] = data.sap_table_field;
            this.getSAPUDF();
            this.showAlert(data.success);
            this.resetFieldData();            
          }
          else//if return object is table_name then get the error
          { 
            let object_name = Object.keys(data)[0];
            this.errorFields[object_name] = data.[object_name][0];
          }

          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
          console.log(error);
          this.disabled = false;
          this.loading = false;
        }
      )
    },

    cancelRowEvent(item) {
      this.editedIndex = this.sap_table_fields.indexOf(item);
      if (this.tableRowMode === "Add") {
        this.sap_table_fields.splice(this.editedIndex, 1);
      } 

      this.resetFieldData();
    },

    editRow(item) {
      this.tableRowMode = "Edit";
      this.editedItem = [];
      this.editedIndex = this.sap_table_fields.indexOf(item);
      this.sap_table_field_options = item.sap_table_field_options;
    },

    removeRow(item) {
      let index = this.sap_table_fields.indexOf(item);
      if(this.mode === 'Add')
      {
        this.sap_table_fields.splice(index, 1);
      }
      else
      { 
        this.showConfirmAlert('Row', item)
      }
    },

    deleteRow(item) {
      const data = { sap_table_field_id: item.id };
  
      this.loading = true;
      axios.post("/api/sap/udf/delete_field", data).then(
        (response) => {
          this.loading = false;
          let data = response.data;

          if(data.success)
          {
            this.showAlert(data.success);
            let index = this.sap_table_fields.indexOf(item);
            this.sap_table_fields.splice(index, 1);
            this.getSAPUDF();
          }
          else
          {
            this.showErrorAlert(data.error)
          }
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    resetData() {
      this.disabled = false;
      this.mode = "";
      this.parent_table_fields = [];
      this.child_tables = [];
      this.child_table_fields = [];
      this.tab = null;
      this.resetRow();
    },

    resetRow() {
      this.editedItem = [];
      this.editedIndex = -1;
      this.tableRowMode = "";
      this.rowUnsaved = false;
    },

    updateScroll(table_id) {
      var element = document.getElementById("table_id");
      element.scrollTop = element.scrollHeight;
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
    },
    formatDate(date) {
      let timestamp = Date.parse(date);

      if (!date || isNaN(timestamp)) return null;
    
      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
    dateMenuSetFalse(table_name, index) {
      this.row_date_menu[table_name][index] = false;
      // console.log(this.row_date_menu[table_name][index]);
    },
    formatDateValue(table_name, i) {
      let value = this.editedItem[table_name].data[i].value;
      this.editedItem[table_name].data[i].formatted_date = this.formatDate(value);
 
    }
  },
  computed: {
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    computedDateFormatted() {
      
      let formattedDates = [];
      this.child_tables.forEach((child, index) => {
        formattedDates[child.table_name] = [];
        this.editedItem[child.table_name].data.forEach((item, i) => {
          console.log(item);
          let formattedDate = this.formatDate(item.value);
          formattedDates[child.table_name].push(formattedDate);
        });
      });
      
      return formattedDates;
    },
    
  },
  watch: {
    $route(to, from) {
      // react to route changes...
      this.getTableFields();
    }
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] = "Bearer " + localStorage.getItem("access_token");
    this.getTableFields();
   
  },
};
</script>