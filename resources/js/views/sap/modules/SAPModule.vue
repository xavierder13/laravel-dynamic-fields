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
                <v-col cols="3" class="mt-0 mb-0 pt-0 pb-0">
                  <template v-if="field.has_options">
                    <v-autocomplete
                      class="pa-0"
                      :label="field.description"
                      :name="field.field_name"
                      :items="field.options"
                      v-model="parent_table_fields[i].value"
                      item-text="description"
                      item-value="value"
                      required
                      dense
                      :error-messages="parent_table_fields[i].errorMsg"
                      @input="validateField('Header', null, i)"
                      @blur="validateField('Header', null, i)"
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
                      class="pa-0"
                      :label="field.description"
                      :name="field.field_name"
                      v-model="parent_table_fields[i].value"
                      dense
                      :error-messages="parent_table_fields[i].errorMsg"
                      @input="validateField('Header', null, i)"
                      @blur="validateField('Header', null, i)"
                      v-if="field.type === 'string'"
                    ></v-text-field>

                    <!-- if Field Type date -->
                    <v-menu
                      ref="menu"
                      class="pa-0"
                      v-model="parent_table_fields[i]['date_menu']"
                      :close-on-content-click="false"
                      transition="scale-transition"
                      offset-y
                      min-width="auto"
                      v-if="field.type === 'date'"
                    >
                      <template v-slot:activator="{ on, attrs }">
                        <v-text-field
                          :label="field.description"
                          :name="field.field_name"
                          v-model="parent_table_fields[i].formatted_date"
                          class="pa-0 ma-0"
                          prepend-icon="mdi-calendar"
                          v-bind="attrs"
                          v-on="on"
                          :error-messages="parent_table_fields[i].errorMsg"
                          @input="validateField('Header', null, i)"
                          @blur="validateField('Header', null, i)"
                        ></v-text-field>
                      </template>
                      <v-date-picker
                        v-model="parent_table_fields[i].value"
                        no-title
                        scrollable
                        @input="formatHeaderDateValue(i)"
                      >
                      </v-date-picker>
                    </v-menu>

                    <!-- if Field Type integer -->
                    <v-text-field-integer
                      class="pa-0"
                      v-model="parent_table_fields[i].value"
                      v-bind:properties="{
                        label: field.description,
                        name: field.field_name,
                        placeholder: '0',
                        dense: true,
                        error: parent_table_fields[i].error,
                        messages: parent_table_fields[i].errorMsg,
                      }"
                      @input="validateField('Header', null, i)"
                      @blur="validateField('Header', null, i)"
                      v-if="field.type === 'integer'"
                    >
                    </v-text-field-integer>

                    <!-- if Field Type decimal -->
                    <v-text-field-money
                      class="pa-0"
                      v-model="parent_table_fields[i].value"
                      v-bind:properties="{
                        label: field.description,
                        name: field.field_name,
                        placeholder: '0',
                        dense: true,
                        error: parent_table_fields[i].error,
                        messages: parent_table_fields[i].errorMsg,
                      }"
                      v-bind:options="{
                        length: 11,
                        precision: 2,
                        empty: null,
                      }"
                      @input="validateField('Header', null, i)"
                      @blur="validateField('Header', null, i)"
                      v-if="field.type === 'decimal'"
                    >
                    </v-text-field-money>
                  </template>
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

                                  <template v-if="row !== editedIndex && item.status !== 'New'">
                                    {{ item[col].value }}
                                  </template>

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
                                        :error-messages="editedItem[child.table_name].data[col].errorMsg"
                                        @input="validateField('Row', child.table_name, col)"
                                        @blur="validateField('Row', child.table_name, col)"
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
                                        class="pa-0"
                                        :name="field.field_name + '[]'"
                                        v-model="editedItem[child.table_name].data[col].value"
                                        dense
                                        hide-details
                                        :error-messages="editedItem[child.table_name].data[col].errorMsg"
                                        @input="validateField('Row', child.table_name, col)"
                                        @blur="validateField('Row', child.table_name, col)"
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
                                            :error-messages="editedItem[child.table_name].data[col].errorMsg"
                                            @input="validateField('Row', child.table_name, col)"
                                            @blur="validateField('Row', child.table_name, col)"
                                          ></v-text-field>
                                        </template>
                                        <v-date-picker
                                          v-model="editedItem[child.table_name].data[col].value"
                                          no-title
                                          scrollable
                                          @input="formatRowDateValue(child.table_name, col)"
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
                                          error: editedItem[child.table_name].data[col].error,
                                          messages: editedItem[child.table_name].data[col].errorMsg,
                                        }"
                                        @input="validateField('Row', child.table_name, col)"
                                        @blur="validateField('Row', child.table_name, col)"
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
                                          error: editedItem[child.table_name].data[col].error,
                                          messages: editedItem[child.table_name].data[col].errorMsg,
                                        }"
                                        v-bind:options="{
                                          length: 11,
                                          precision: 2,
                                          empty: null,
                                        }"
                                        @input="validateField('Row', child.table_name, col)"
                                        @blur="validateField('Row', child.table_name, col)"
                                        v-if="field.type === 'decimal'"
                                      >
                                      </v-text-field-money>
                                    </template>
                                  </template>
                                </td>
                                <template v-if="row !== editedIndex && item.status !== 'New' ">
                                  <td class="pa-2">
                                    <v-icon
                                      small
                                      class="mr-2"
                                      color="green"
                                      @click="editRow(child.table_name, item)"
                                      :disabled="tableRowMode === 'Add' ? true : false"
                                    >
                                      mdi-pencil
                                    </v-icon>

                                    <v-icon
                                      small
                                      color="red"
                                      @click="removeRow(item)"
                                      :disabled="['Add', 'Edit'].includes(tableRowMode)"
                                    >
                                      mdi-delete
                                    </v-icon>
                                  </td>
                                </template>
                                <template v-if="row === editedIndex ? true : false || item.status === 'New' ">
                                  <td class="pa-2">
                                    <v-btn
                                      x-small
                                      :disabled="disabled"
                                      @click="saveRow(child.table_name)"
                                      icon
                                    >
                                      <v-icon color="primary">mdi-content-save</v-icon>
                                    </v-btn>
                                    <v-btn
                                      x-small
                                      color="#E0E0E0"
                                      @click="cancelRowEvent(child.table_name)"
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
      editedIndex: [],
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
          
          this.parent_table = data.parent_table;
          this.child_tables = data.child_tables;

          // breadcrumbs
          this.items[1].text = 'Create ' + this.parent_table.description;

          let parent_table_fields = data.parent_table_fields;
          let child_table_fields = data.child_table_fields;

          parent_table_fields.forEach((value, index) => {
            
            this.parent_table_fields.push({
              value: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
              is_required: value.is_required,
              formatted_date: null,
              error: false,
              errorMsg: "",
            });

          });

          this.child_tables.forEach((value, index) => {

            let table_name = value.table_name;

            this.child_table_fields[table_name] = Object.assign({}, { fields: [], data: [] });
            this.editedItem[table_name] = Object.assign({}, { data: [] });

            child_table_fields.forEach((val, i) => {

              if(table_name === val.sap_table.table_name)
              {
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
                  fomatted_date: null,
                  error: false,
                  errorMsg: "",
                });
              }

            });

          });

          this.overlay = false;

        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    saveData() {
      this.disabled = true;

      this.parent_table_fields.forEach((value, i) => {
        this.validateField('Header', null, i);
      });

      this.child_tables.forEach((value, index) => {
        editedItem[table_name].data.forEach((val, i) => {
          this.validateField('Row', table_name, i);
        });
      });
    
      if(!this.headerError)
      {
        this.getTableFields();
        this.overlay = true;
      }

      this.disabled = false
      
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
      // this.editedIndex[table_name] =  await data.length - 1;
      
      // auto scroll down when adding an item
      // await this.updateScroll(null);

      this.tab = null; // set tab model value into null to update/refresh content
      this.tab = tab_index; // set set tab model value into current index

    },

    saveRow(table_name) {
      this.editedIndex = 1;
      console.log(this.editedIndex);
      let data = this.child_table_fields[table_name].data;
      let editedItem = this.editedItem[table_name];
      
      editedItem.data.forEach((val, i) => {
        this.validateField('Row', table_name, i);
      });
      
      // console.log(this.editedItem[table_name]);
      // console.log(this.rowError);

      let index = data.indexOf({ status: 'New' }); 

      data.splice(index, 1);

      let arrData = []; 

      editedItem.data.forEach((val, i) => {
         arrData.push({
            value: val.value,
            field_name: val.field_name,
            description: val.description, 
            type: val.type,
            has_options: val.has_options,
            options: val.sap_table_field_options,
          });
      });

      data.push(arrData);

      // console.log(data);

      this.resetRow();
  
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

    cancelRowEvent(table_name) {
      this.editedIndex = this.child_table_fields[table_name].data.indexOf({ status: "New" });
      if (this.tableRowMode === "Add") {
        this.child_table_fields[table_name].data.splice(this.editedIndex, 1);
      }
      this.resetRow();
    },

    editRow(table_name, item) {
      console.log(item);
      let data = this.child_table_fields[table_name].data;
      this.editedIndex = data.indexOf(item);
      let row_data = data[this.editedIndex];
      
      row_data.forEach((val, i) => {
        
        this.editedItem[table_name].data[i].value = val.value;

      });

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
      // this.editedItem = [];
      this.editedIndex = -1;
      this.tableRowMode = "";
      this.rowUnsaved = false;

      // reset editedItem values
      this.child_tables.forEach((value, index) => {
        let data = this.editedItem[value.table_name].data;
        data.forEach((val, i) => {
          data[i] = Object.assign(data[i], {
            value: "",
            date_menu: false,
            fomatted_date: null,
            error: false,
            errorMsg: "",
          });


          
        });
          
      })
    },

    validateField(table_type, child_table, row) {

      let type = "";
      let spChars1 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/; //all special characters
      let spChars2 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,<>\/?]+/; //all special characters whithout period/dot (.)
      let invalid = false;
      let field = table_type == 'Header' ? this.parent_table_fields[row] : this.editedItem[child_table].data[row] ;
      let value = field.value;

      field.error = false;
      field.errorMsg = "";
      type = field.type;   

      if(field.is_required)
      {
        if(!value)
        {
          field.error = true;
          field.errorMsg = field.description + ' is required';
        }
      }

      if(type === 'integer')
      { 
        // validate integer with whole number only without period (.)
        invalid = parseInt(value) && !spChars1.test(value) ? false : true;
      }
      else if(type === 'decimal')
      {
        invalid = parseFloat(value) && !spChars2.test(value) ? false : true;
      }
      else if(type === 'date')
      {
        let dateString = value;
        let timestamp = Date.parse(dateString);

        invalid = isNaN(timestamp) ? true : false;
      }

      if(!field.error)
      {
        if(invalid)
        {
          field.error = true;
          field.errorMsg = field.description + ' must be type ' + field.type;
        }
      }
        
    },

    validateRow() {

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

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
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
    formatHeaderDateValue(i) {
      let value = this.parent_table_fields[i].value;
      this.parent_table_fields[i].formatted_date = this.formatDate(value);

    },
    formatRowDateValue(table_name, i) {
      let value = this.editedItem[table_name].data[i].value;
      this.editedItem[table_name].data[i].formatted_date = this.formatDate(value);
 
    },

    rowFieldColor(table_name, index){
      // if edit mode then set the color of edited row into 'red lighten-5' or 'blue lighten-5' else ''
      return index === this.editedIndex[table_name] ? this.rowError ? 'red lighten-5' : 'blue lighten-5' : ''
    },
    
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
    headerError() {
      let hasError = false;
      this.parent_table_fields.forEach(value => {
        if(value.error)
        {
          hasError = true;
        }
      });

      return hasError;
    },
    rowError() {
      let hasError = false;
      
      this.child_tables.forEach((value, index) => {

        let data = this.editedItem[value.table_name].data;
        data.forEach((val, i) => {
          if(val.error)
          {
            hasError = true;
          }
        });
        
      });
      return hasError;
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