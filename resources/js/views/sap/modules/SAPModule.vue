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
            <span class="headline">{{ parent_table.description }}</span>
            <v-divider vertical class="mx-2"></v-divider>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon 
                  v-bind="attrs" 
                  v-on="on" 
                  large 
                  color='blue darken-2' 
                  :disabled="mode === 'Add'" 
                  @click="getTableFields() + (mode = 'Add')"
                >
                  mdi-file-plus
                </v-icon>
              </template>
              <span>Add Mode</span>
            </v-tooltip>
            <v-tooltip top>
              <template v-slot:activator="{ on, attrs }">
                <v-icon 
                  v-bind="attrs" 
                  v-on="on" 
                  large 
                  color='blue darken-2' 
                  :disabled="mode === 'Find'" 
                  @click="getTableFields() + (mode = 'Find')"
                >
                mdi-file-find
              </v-icon>
              </template>
              <span>Find Mode</span>
            </v-tooltip>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="pa-6">
            <v-row>
              <template v-for="(field, i) in parent_table_fields.data">
                <v-col cols="3" class="mt-0 mb-0 pt-0 pb-0">
                  
                  <template v-if="field.has_options">
                    <v-autocomplete
                      class="pa-0"
                      :label="field.description + (field.is_required ? ' *' : '')"
                      :name="field.field_name"
                      :items="field.options"
                      v-model="field.value"
                      item-text="description"
                      item-value="value"
                      required
                      dense
                      :error-messages="field.errorMsg"
                      @input="validateField('Header', i, field.value)"
                      @blur="validateField('Header', i, null)"
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
                      :label="field.description + (field.is_required ? ' *' : '')"
                      :name="field.field_name"
                      v-model="field.value"
                      dense
                      :error-messages="field.errorMsg"
                      @input="validateField('Header', i, null)"
                      @blur="validateField('Header', i, null)"
                      v-if="['string', 'date'].includes(field.type)"
                    ></v-text-field>

                    <!-- if Field Type date -->
                    <!-- <v-menu
                      ref="menu"
                      class="pa-0"
                      v-model="parent_table_fields[i].date_menu"
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
                          @input="validateField('Header', i, null)"
                          @blur="validateField('Header', i, null)"
                        ></v-text-field>
                      </template>
                      <v-date-picker
                        v-model="parent_table_fields[i].value"
                        no-title
                        scrollable
                        @input="formatHeaderDateValue(i) + (parent_table_fields[i].date_menu = false)"
                      >
                      </v-date-picker>
                    </v-menu> -->

                    <!-- if Field Type integer -->
                    <v-text-field-integer
                      class="pa-0"
                      v-model="field.value"
                      :label="field.description + (field.is_required ? ' *' : '')"
                      v-bind:properties="{
                        name: field.field_name,
                        placeholder: '0',
                        dense: true,
                        error: field.error,
                        messages: field.errorMsg,
                      }"
                      @input="validateField('Header', i, null)"
                      @blur="validateField('Header', i, null)"
                      v-if="field.type === 'integer'"
                    >
                    </v-text-field-integer>

                    <!-- if Field Type decimal -->
                    <v-text-field-money
                      class="pa-0"
                      v-model="parent_table_fields[i].value"
                      :label="field.description + (field.is_required ? ' *' : '')"
                      v-bind:properties="{
                        name: field.field_name,
                        placeholder: '0',
                        dense: true,
                        error: field.error,
                        messages: field.errorMsg,
                      }"
                      v-bind:options="{
                        length: 11,
                        precision: 2,
                        empty: null,
                      }"
                      @input="validateField('Header', i, null)"
                      @blur="validateField('Header', i, null)"
                      v-if="field.type === 'decimal'"
                    >
                    </v-text-field-money>
                  </template>
                </v-col>
              </template>
            </v-row>
            <v-row v-if="child_tables.length && mode !== 'Find'">
              <v-col>
                <v-card>
                  <v-card-text>
                    <v-tabs v-model="tab" ref="child_table_tabs">
                      <v-tab v-for="(child, i) in child_tables" :key="child.table_name">
                        {{ child.description }}

                      </v-tab>
                    </v-tabs>
                    <v-tabs-items v-model="tab">
                      <v-tab-item v-for="(child, i) in child_tables" :key="child.table_name">
                        <v-simple-table 
                          class="elevation-1 child_table" 
                          :id="'child_table' + i" 
                          style="max-height: 250px; overflow-y: auto; !important"
                        >
                          <template v-slot:default>
                            <thead>
                              <tr>
                                <th class="pa-2" width="10px">#</th>
                                <th class="pa-2" v-for="(field, j) in child_table_fields[i].fields" :key="j"> 
                                  {{ field.description + (field.is_required ? ' *' : '') }}
                                </th>
                                <th class="pa-2" width="80px"> Actions</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(item, row) in child_table_fields[i].data" :key="row" :class="rowFieldColor(i, row)">
                                <td class="pa-2"> {{ row + 1 }} </td>
                                <td class="pa-2" v-for="(field, col) in child_table_fields[i].fields" :key="col" >

                                  <template v-if="row !== editedIndex[i].index && item.status !== 'New'">
                                    {{ item[col].value }}
                                  </template>

                                  <template v-if="row === editedIndex[i].index || item.status === 'New'">
                                    <!-- if Field has Options -->
                                    <template v-if="field.has_options">
                                      <v-autocomplete
                                        class="pa-0"
                                        :name="field.field_name + '[]'"
                                        :items="field.options"
                                        v-model="editedItem[i].data[col].value"
                                        item-text="description"
                                        item-value="value"
                                        required
                                        dense
                                        hide-details
                                        :error-messages="editedItem[i].data[col].errorMsg"
                                        @input="validateField('Row', col, i)"
                                        @blur="validateField('Row', col, i)"
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
                                        v-model="editedItem[i].data[col].value"
                                        dense
                                        hide-details
                                        :placeholder="field.type === 'date' ? 'MM/DD/YYYY' : ''"
                                        :error-messages="editedItem[i].data[col].errorMsg"
                                        @input="validateField('Row', col, i)"
                                        @blur="validateField('Row', col, i)"
                                        v-if="['string', 'date'].includes(field.type)"
                                      ></v-text-field>

                                      <!-- if Field Type date -->
                                      <!-- <v-menu
                                        ref="menu"
                                        class="pa-0"
                                        v-model="editedItem[i].data[col].date_menu"
                                        :close-on-content-click="false"
                                        transition="scale-transition"
                                        offset-y
                                        min-width="auto"
                                        v-if="field.type === 'date'"
                                        @click="validateField('Row', col, i)"
                                      >
                                        <template v-slot:activator="{ on, attrs }">
                                          <v-text-field
                                            :name="field.field_name + '[]'"
                                            v-model="editedItem[i].data[col].formatted_date"
                                            class="pa-0"
                                            prepend-icon="mdi-calendar"
                                            v-bind="attrs"
                                            v-on="on"
                                            hide-details
                                            :error-messages="editedItem[i].data[col].errorMsg"
                                            @input="validateField('Row', col, i)"
                                            @blur="validateField('Row', col, i)"
                                          ></v-text-field>
                                        </template>
                                        <v-date-picker
                                          v-model="editedItem[i].data[col].value"
                                          no-title
                                          scrollable
                                          @input="formatRowDateValue(col, i) + (editedItem[i].data[col].date_menu = false)"
                                        >
                                        </v-date-picker>
                                      </v-menu> -->

                                      <!-- if Field Type integer -->
                                      <v-text-field-integer
                                        class="pa-0"
                                        v-model="editedItem[i].data[col].value"
                                        v-bind:properties="{
                                          name: field.field_name + '[]',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                          error: editedItem[i].data[col].error,
                                          messages: editedItem[i].data[col].errorMsg
                                        }"
                                        @input="validateField('Row', col, i)"
                                        @blur="validateField('Row', col, i)"
                                        v-if="field.type === 'integer'"
                                      >
                                      </v-text-field-integer>

                                      <!-- if Field Type decimal -->
                                      <v-text-field-money
                                        class="pa-0"
                                        v-model="editedItem[i].data[col].value"
                                        v-bind:properties="{
                                          name: field.field_name + '[]',
                                          placeholder: '0',
                                          'hide-details': true,
                                          dense: true,
                                          error: editedItem[i].data[col].error,
                                          messages: editedItem[i].data[col].errorMsg
                                        }"
                                        v-bind:options="{
                                          length: 11,
                                          precision: 2,
                                          empty: null,
                                        }"
                                        @input="validateField('Row', col, i)"
                                        @blur="validateField('Row', col, i)"
                                        v-if="field.type === 'decimal'"
                                      >
                                      </v-text-field-money>
                                    </template>
                                  </template>
                                </td>
                                <template v-if="row !== editedIndex[i].index && item.status !== 'New' ">
                                  <td class="pa-2">
                                    <v-icon
                                      small
                                      class="mr-2"
                                      color="green"
                                      @click="editRow(i, item)"
                                      :disabled="tableRowMode[i].mode === 'Add' ? true : false"
                                    >
                                      mdi-pencil
                                    </v-icon>

                                    <v-icon
                                      small
                                      color="red"
                                      @click="removeRow(i, item)"
                                      :disabled="['Add', 'Edit'].includes(tableRowMode[i].mode)"
                                    >
                                      mdi-delete
                                    </v-icon>
                                  </td>
                                </template>
                                <template v-if="row === editedIndex[i].index ? true : false || item.status === 'New' ">
                                  <td class="pa-2">
                                    <v-btn
                                      x-small
                                      :disabled="disabled"
                                      @click="saveRow(i)"
                                      icon
                                    >
                                      <v-icon color="primary">mdi-content-save</v-icon>
                                    </v-btn>
                                    <v-btn
                                      x-small
                                      color="#E0E0E0"
                                      @click="cancelRowEvent(i)"
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
                                <td :colspan="child_table_fields[i].fields.length + 2" class="text-right">
                                  <v-btn class="primary" x-small @click="newRow(i)">add item</v-btn>
                                </td>
                              </tr>
                            </tfoot>
                          </template>
                        </v-simple-table>
                        <v-alert
                          dense
                          outlined
                          type="error"
                          class="pa-1 mt-2 mb-0"
                          v-if="tableHasError[i].error || rowUnsaved[i].status"
                        >
                          {{ tableHasError[i].errorMsg + ' ' + rowUnsaved[i].errorMsg }}
                        </v-alert>
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
              @click="submitData()"
              :disabled="disabled"
              class="ml-6 mb-4 mr-1"
            >
              {{ mode === 'Edit' ? 'Update' : mode }}
            </v-btn>
            <v-btn color="#E0E0E0" to="/" class="mb-4"> Cancel </v-btn>
          </v-card-actions>
        </v-card>
        <v-dialog v-model="dialog" max-width="1000px" persistent>
          <v-card>
            <v-card-title>
              <span class="headline">List of {{ parent_table.description }}</span>
                <v-spacer></v-spacer>
                <v-text-field
                  v-model="search"
                  append-icon="mdi-magnify"
                  label="Search"
                  single-line
                ></v-text-field>
                <v-spacer></v-spacer>

                <v-icon @click="dialog = false">mdi-close</v-icon>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="search_headers"
                :items="search_list"
                :search="search"
                :loading="loading"
                loading-text="Loading... Please wait"
                class="elevation-1 "
              > 
                <template v-slot:item="{ item }">
                  <tr @click="selectSearchList(item)" style="cursor: pointer;">
                    <td v-for="column in search_headers"> 
                      {{ formatDate(item[column.value]) ? formatDate(item[column.value]) : item[column.value] }}
                    </td>
                  </tr>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-dialog>
      </v-main>
    </div>
  </div>
</template>
<style>
.child_table th, .child_table td { border:1px solid #dddddd; border-bottom:1px solid #dddddd;}
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
import moment from "moment";


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
      parent_table_fields: "",
      child_tables: [],
      child_table_fields: [],
      editedIndex: [],
      editedItem: [],
      defaultItem: [],
      tab: null,
      mode: "Add",
      tableRowMode: [],
      rowUnsaved: [],
      date_menu: false,
      row_date_menu: [],
      formattedDateValue: [],
      tableHasError: [],
      search: "",
      search_list: [],
      search_headers: [],
      loading: false,
      dialog: false,
      card_width: "",
    };
  },

  methods: {
    getTableFields() {

      this.resetData();

      let sap_table_id = this.$route.params.sap_table_id;
      axios.get("/api/sap/module/"+ sap_table_id).then(
        (response) => {
      
          let data = response.data;
          
          this.parent_table = data.parent_table;
          this.child_tables = data.child_tables;

          // breadcrumbs
          this.items[1].text = 'Create ' + this.parent_table.description;

          let parent_table_fields = data.parent_table_fields;
          let child_table_fields = data.child_table_fields;

          this.parent_table_fields = Object.assign({}, { table_name: this.parent_table.table_name, data: []});

          // assign header fields
          parent_table_fields.forEach((value, index) => {
            
            this.parent_table_fields.data.push({
              value: '',
              field_name: value.field_name,
              description: value.description, 
              type: value.type,
              is_required: value.is_required,
              date_menu: false,
              formatted_date: null,
              error: false,
              errorMsg: "",
            });

          });

          // assign row fields per tab/child table
          this.child_tables.forEach((value, index) => {

            let table_name = value.table_name;

            this.child_table_fields[index] = Object.assign({}, { table_name: table_name, fields: [], data: [] });
            this.editedItem[index] = Object.assign({}, { data: [] });
            this.editedIndex[index] = Object.assign({ index: -1 });
            this.tableHasError[index] = Object.assign({ error: false, errorMsg: "" });
            this.tableRowMode[index] =  Object.assign({ mode: "" });
            this.rowUnsaved[index] =  Object.assign({ status: false, errorMsg: "" });

            child_table_fields.forEach((val, i) => {

              if(table_name === val.sap_table.table_name)
              {
                this.child_table_fields[index].fields.push({
                  field_name: val.field_name,
                  description: val.description, 
                  type: val.type,
                  is_required: val.is_required,
                  has_options: val.has_options,
                  options: val.sap_table_field_options,
                });

                this.editedItem[index].data.push({
                  value: '',
                  field_name: val.field_name,
                  description: val.description, 
                  type: val.type,
                  is_required: val.is_required,
                  date_menu: false,
                  formatted_date: null,
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

    findData() {
      let sap_table_id = this.$route.params.sap_table_id;
      this.search_list = [];
      this.search_headers = [];
      axios.post('/api/sap/module/find/'+sap_table_id, this.parent_table_fields).then(
        (response) => {
          let data = response.data.data;
          let table_fields = response.data.table_fields;
    
          if(data.length)
          {
            this.dialog = true;
            this.search_headers.push({text: 'ID', value: 'id'});
            table_fields.forEach(value => {
              this.search_headers.push({text: value.description, value: value.field_name});
            });
            this.search_list = data;
          }
          else
          {
            this.$swal({
              position: "center",
              icon: "error",
              title: "No record found",
              showConfirmButton: false,
              timer: 2500,
            });
          }
          
        },
        (error) => {

        },
      )
    },

    selectSearchList(item) {
    
      let sap_table_id = this.$route.params.sap_table_id;
      const data = { sap_table_id: sap_table_id, id: item.id };
      axios.post('/api/sap/module/data', data).then(
        (response) => {
          console.log(response.data);
          let data = response.data;
          let header_data = data.header_data;
          let row_data = data.row_data;

          this.parent_table_fields.data.forEach((value, index) => {
            let field_value = header_data[value.field_name];

            field_value = value.type === 'date' ? this.formatDate(field_value) : field_value;

            // this.parent_table_fields.data[index].value = field_value;
            // this.parent_table_fields.data[index].formatted_date = this.formatDate(header_data[value.field_name]);

            value.value = field_value;
            value.formatted_date = this.formatDate(header_data[value.field_name]);

          });

          this.parent_table_fields = Object.assign(this.parent_table_fields, { id: header_data.id });
          
          this.child_table_fields.forEach((value, index) => {
            
            row_data.forEach((row, i) => {

              if(value.table_name === row.table_name)
              { 
                let fields = this.child_table_fields[index].fields;
                let table_data = row.data;

                table_data.forEach(data => {
                  let arrData = [];
                  fields.forEach(field => {
                    let field_value = data[field.field_name];

                    field_value = field.type === 'date' ? this.formatDate(field_value) : field_value;

                    arrData.push({
                      value: field_value,
                      field_name: field.field_name,
                      description: field.description, 
                      type: field.type,
                      has_options: field.has_options,
                      options: field.options,
                    });
                    
                  });

                  this.child_table_fields[index].data.push(arrData);
                  
                  
                });

              }

            });
            
          });

          console.log('header', this.parent_table_fields);
          console.log('row', this.child_table_fields);
          
          this.mode = "Edit";
          this.dialog = false;
        },
        (error) => {
          console.log(response);
        }
      )

    },

    submitData() {

      if(this.mode === 'Find') 
      {
        this.findData();
      }
      else
      {
        this.parent_table_fields.data.forEach((value, i) => {
          this.validateField('Header', i, null);
        });

        this.child_tables.forEach((value, index) => {
          this.rowUnsaved[index].status = this.tableRowMode[index].mode ? true : false;
          this.rowUnsaved[index].errorMsg = this.rowUnsaved[index].status ? 'There are unsaved data' : '';
        });
      
        if(!this.headerError && !this.rowError() )
        {

          this.overlay = true;
          this.disabled = true;
          this.saveData();
        }
      }
    },

    async newRow(tab_index)
    {
 
      this.tableRowMode[tab_index].mode = "Add";
      let data = this.child_table_fields[tab_index].data;

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
      this.editedIndex[tab_index].index =  await data.length - 1;
      
      // auto scroll down when adding an item
      
      await this.refreshTabData(tab_index);
      await this.updateScroll(tab_index);

    },

    saveRow(tab_index) {
      let data = this.child_table_fields[tab_index].data;
      let editedItem = this.editedItem[tab_index];
      
      editedItem.data.forEach((val, i) => {
        this.validateField('Row', i, tab_index);
      });

      if(this.mode === 'Add')
      {
        let index = data.indexOf({ status: 'New' }); 

        if(!this.tableHasError[tab_index].error)
        {
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
          this.refreshTabData(tab_index);
          this.resetRow(tab_index);
        }
        
      }

      
    },

    saveData() {
      
      let url = this.mode === 'Add' ? '/api/sap/module/store' : '';

      const data = {
        header: this.parent_table_fields,
        row: this.child_table_fields,
      }

      axios.post(url, data).then(
        (response) => {
          console.log(response);
          let data = response.data;
          if(data.success)
          {
            this.showAlert(data.success);
            this.resetData();
            this.getTableFields();
          }
          
        },
        (error) => {

        }
      )

      this.disabled = false;
      this.overlay = false;
    },

    updateData() {

    },

    storeRow(tab_index) { 
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
            this.showAlert(data.success);
            this.resetRow(tab_index);
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

    updateRow(tab_index) {
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
            this.showAlert(data.success);
            this.resetRow(tab_index);            
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

    cancelRowEvent(tab_index) {

      let index = this.child_table_fields[tab_index].data.indexOf({ status: "New" });

      if (this.tableRowMode[tab_index].mode === "Add") {
        this.child_table_fields[tab_index].data.splice(index, 1);
      }
      this.resetRow(tab_index);
    },

    editRow(tab_index, item) {
 
      let data = this.child_table_fields[tab_index].data;
      this.editedIndex[tab_index].index = data.indexOf(item);
      let row_data = data[this.editedIndex[tab_index].index];
      
      row_data.forEach((val, i) => {
        
        // this.editedItem[tab_index].data[i].value = val.value;
        // this.editedItem[tab_index].data[i].formatted_date = this.formatDate(val.value);

        val.value = val.value;
        val.formatted_date = this.formatDate(val.value);

      });

      this.refreshTabData(tab_index);

    },

    removeRow(tab_index, item) {
      let data = this.child_table_fields[tab_index].data;
      let index = data.indexOf(item);

      if(this.mode === 'Add')
      {
        data.splice(index, 1);
      }
      else
      { 
        this.showConfirmAlert('Row', tab_index, item)
      }

      this.refreshTabData(tab_index);
    },

    deleteRow(tab_index, item) {
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
      this.mode = "Add";
      this.parent_table_fields = "";
      this.child_tables = [];
      this.child_table_fields = [];
      this.tab = null;
      this.child_tables.forEach((val, i) => {
        this.resetRow(i);
      }); 
      
    },

    resetRow(tab_index) {
      // this.editedItem = [];
      this.tableRowMode[tab_index].mode = "";
      this.rowUnsaved[tab_index] = Object.assign({status: false, errorMsg: ""});
      this.editedIndex[tab_index].index = -1;
      this.tableRowMode[tab_index].mode = "";
      this.tableHasError[tab_index] =  Object.assign({}, {error: false, errorMsg: ""});
      // reset editedItem values
      let data = this.editedItem[tab_index].data;
      data.forEach((val, i) => {
        data[i] = Object.assign(data[i], {
          value: "",
          date_menu: false,
          formatted_date: null,
          error: false,
          errorMsg: "",
        });
      });

      this.refreshTabData(tab_index);
    },

    validateField(table_type, row, tab_index) {
      // validate when mode not equal to 'fidn'
      if(this.mode !== 'Find')
      {
        let type = "";
        let spChars1 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/; //all special characters
        let spChars2 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,<>\/?]+/; //all special characters whithout period/dot (.)
        let invalid = false;
        let field = table_type == 'Header' ? this.parent_table_fields.data[row] : this.editedItem[tab_index].data[row] ;
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

        // if has value then validate
        if(value)
        {
          if(type === 'integer')
          { 
            // validate integer with whole number only without period (.)
            invalid = !isNaN(parseInt(value)) && !spChars1.test(value) ? false : true;
          }
          else if(type === 'decimal')
          {
            invalid = !isNaN(parseFloat(value)) && !spChars2.test(value) ? false : true;
          }
          else if(type === 'date')
          {
            let dateIsValid = moment(value, 'M/D/YYYY',true).isValid();
            invalid = dateIsValid ? false : true;
          }
        }

        if(!field.error)
        {
          if(invalid)
          {
            field.error = true;
            let str = "";
            if(type === 'date')
            {
              str = " (MM/DD/YYYY) format"
            }
            field.errorMsg = field.description + ' must be type ' + field.type + str;
          }
        }
        
        // scan if tab items has error
        this.child_tables.forEach((value, index) => {
          this.tableHasError[index] = Object.assign({}, {error: false, errorMsg: ""});
          let errorMsg = [];
          let hasError = false;
          this.editedItem[index].data.forEach((val, i) => {
            if(val.error)
            {
              hasError = true;
              errorMsg.push(val.errorMsg);
            }
          });
          this.tableHasError[index] = Object.assign({}, {error: hasError, errorMsg: errorMsg.join(', ')});
        });

        if(table_type === 'Row')
        {
          this.refreshTabData(tab_index);
        }

        this.rowUnsaved[tab_index] = Object.assign({status: false, errorMsg: ""});
      }
      
    },

    refreshTabData(tab_index) {
      this.tab = null;
      this.tab = tab_index;
    },

    rowError() {
      let hasError = false;
      let tab_index = null;
      this.child_tables.forEach((value, index) => {
        if(this.rowUnsaved[index].status)
        {
          hasError = true;
          tab_index = index;
        }
      });

      this.tab = tab_index;
  
      return hasError;
    },

    validateRow() {

    },

    updateScroll(tab_index) {
      var element = document.getElementById("child_table"+tab_index);
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
      // let timestamp = Date.parse(date);

      // if (!date || isNaN(timestamp)) return null;
      
      // const [year, month, day] = date.split("-");
      // return `${month}/${day}/${year}`;

      var date_val = moment(date, 'YYYY-MM-DD',true);
      if (!date || !date_val.isValid()) return null;

      return moment(date).format('MM/DD/YYYY');
    
    },
 
    formatHeaderDateValue(i) {
      this.validateField('Header', i, null);
      let value = this.parent_table_fields.data[i].value;
      this.parent_table_fields.data[i].formatted_date = this.formatDate(value);
    },
    formatRowDateValue(i, tab_index) {
      this.validateField('Row', i, tab_index);
      let value = this.editedItem[tab_index].data[i].value;
      this.editedItem[tab_index].data[i].formatted_date = this.formatDate(value);
      this.refreshTabData(tab_index);
    },

    rowFieldColor(tab_index, index){
      // if edit mode then set the color of edited row into 'red lighten-5' or 'blue lighten-5' else ''
      let className = '';
      if(index === this.editedIndex[tab_index].index)
      {
        if(this.tableHasError[tab_index].error || this.rowUnsaved[tab_index].status)
        {
          className = 'red lighten-5';
        }
        else
        {
          className = 'blue lighten-5';
        }
      }

      return className;
    },
    
  },
  computed: {
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    headerError() {
      let hasError = false;
      this.parent_table_fields.data.forEach(value => {
        if(value.error)
        {
          hasError = true;
        }
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