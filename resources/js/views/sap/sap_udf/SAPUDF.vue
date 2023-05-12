<template>
  <div class="flex column">
    <div id="_wrapper" class="pa-5">
      <v-main>
        <v-breadcrumbs :items="items">
          <template v-slot:item="{ item }">
            <v-breadcrumbs-item :to="item.link" :disabled="item.disabled">
              {{ item.text.toUpperCase() }}
            </v-breadcrumbs-item>
          </template>
        </v-breadcrumbs>
        <v-card>
          <v-card-title>
            SAP UDF Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="newSAPTable()"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" persistent>
                  <v-card>
                    <v-card-title class="pa-4">
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider class="mt-0"></v-divider>
                    <v-card-text>
                      <!-- START Show Table Name, Description and Type if Edit mode for type Header or Add mode -->
                      <template v-if="(mode === 'Edit' && editedItem.id) || mode === 'Add'">
                        <v-row>
                          <v-col>
                            <v-text-field
                              name="table_name"
                              v-model="editedItem.table_name"
                              label="Table Name"
                              required
                              :error-messages="tableNameErrors"
                              @input="$v.editedItem.table_name.$touch() + (errorFields.table_name = '')"
                              @blur="$v.editedItem.table_name.$touch()"
                            ></v-text-field>
                          </v-col>
                          <v-col>
                            <v-text-field
                              name="description"
                              v-model="editedItem.description"
                              label="Description"
                              required
                              :error-messages="tableDescriptionErrors"
                              @input="$v.editedItem.description.$touch()"
                              @blur="$v.editedItem.description.$touch()"
                            ></v-text-field>
                          </v-col>
                          <v-col>
                            <v-autocomplete
                              name="type"
                              :items="table_types"
                              v-model="editedItem.type"
                              item-text="type"
                              item-value="value"
                              label="Type"
                              required
                              :error-messages="tableTypeErrors"
                              @input="$v.editedItem.type.$touch()"
                              @blur="$v.editedItem.type.$touch()"
                            ></v-autocomplete>
                          </v-col>
                          <v-col v-if="parentTableIsRequired">
                            <v-autocomplete
                              name="parent_table"
                              :items="parent_tables"
                              v-model="editedItem.parent_table"
                              item-text="table_name"
                              item-value="table_name"
                              label="Parent Table"
                              required
                              :error-messages="parentTableErrors"
                              @input="$v.editedItem.parent_table.$touch()"
                              @blur="$v.editedItem.parent_table.$touch()"
                            >
                              <template slot="selection" slot-scope="data">
                                {{ data.item.table_name + ' - ' + data.item.description }}
                              </template>
                              <template slot="item" slot-scope="data">
                                {{ data.item.table_name + ' - ' + data.item.description }}
                              </template>
                            </v-autocomplete>
                          </v-col>
                        </v-row>
                        <v-divider></v-divider>
                      </template>
                       <!-- END Show Table Name, Description and Type if Edit mode for type Header or Add mode -->
                      <v-row>
                        <v-col>
                          <v-card>
                            <v-card-title class="subtitle-1">Table Fields</v-card-title>
                            <v-card-text>
                              <v-simple-table 
                                class="elevation-1" 
                                id="sap_table_fields" 
                                style="max-height: 250px; overflow-y: scroll; overflow-y: auto !important"
                              >
                                <template v-slot:default>
                                  <thead>
                                    <tr>
                                      <th class="pa-2" width="10px">#</th>
                                      <th class="pa-2">Field Name</th>
                                      <th class="pa-2">Description</th>
                                      <th class="pa-2" width="120px">Type</th>
                                      <th class="pa-2" width="60px">Length</th>
                                      <th class="pa-2">Default Value</th>
                                      <th class="pa-2">Has Options</th>
                                      <th class="pa-2">Required</th>
                                      <th class="pa-2" width="80px"> Actions</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(item, index) in sap_table_fields" :class="rowFieldColor(index)">
                                      <td class="pa-2"> {{ index + 1 }} </td>

                                      <!-- START Show Data if row is not for edit (show by default) -->
                                      <template v-if="index !== editedFieldIndex && item.status !== 'New'">
                                        <td class="pa-2"> {{ item.field_name }} </td>
                                        <td class="pa-2"> {{ item.description }}</td>
                                        <td class="pa-2"> {{ item.type }} </td>
                                        <td class="pa-2"> {{ item.length }} </td>
                                        <td class="pa-2"> {{ item.default_value }}</td>
                                        <td> <v-icon color="primary" v-if="item.has_options"> mdi-check </v-icon> </td>
                                        <td> <v-chip :color="item.is_required ? 'primary' : 'seconday'" small> {{ item.is_required ? 'Required' : 'Nullable' }} </v-chip></td>
                                      </template>
                                      <!-- END Show Data if row is not for edit (show by default) -->

                                      <!-- START Show Fields if row is for edit -->
                                      <template v-if="index === editedFieldIndex || item.status === 'New'">
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
                                      <!-- END Show Fields if row is for edit -->
                                      
                                      <!-- START Show Edit(pencil icon) and Delete (trash icon) button if not Edit Mode (show by default) -->
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
                                      <!-- END  Show Edit(pencil icon) and Delete (trash icon) button if not Edit Mode (show by default) -->

                                      <!-- START  Show Save and Cancel button if Edit Mode -->
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
                                      </template>
                                      <!-- END  Show Save and Cancel button if Edit Mode -->
                                    </tr>
                                  </tbody>
                                  <tfoot>
                                    <tr>
                                      <td colspan="9" class="text-right" v-if="editedItem.id || mode === 'Add'">
                                        <v-btn class="primary" x-small @click="newFieldItem()" :disabled="['Add', 'Edit'].includes(tableFieldsMode)">add item</v-btn>
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
                                v-if="fieldListError.status || fieldUnsaved"
                              >
                                {{ fieldListError.errorMsg }}
                              </v-alert>
                            </v-card-text>
                          </v-card>
                        </v-col>
                        <v-divider vertical v-if="fieldHasOptions"></v-divider>
                        <v-col cols="4" v-if="fieldHasOptions">
                          <v-card>
                            <v-card-title class="subtitle-1">Field Options</v-card-title>
                            <v-card-text>
                              <v-simple-table 
                                class="elevation-1" 
                                id="sap_table_field_options" 
                                style="max-height: 250px; overflow-y: scroll; overflow-y: auto !important">
                                <template v-slot:default>
                                  <thead>
                                    <tr>
                                      <th class="pa-2" width="10px">#</th>
                                      <th class="pa-2">Value</th>
                                      <th class="pa-2">Description</th>
                                      <th class="pa-2" width="80px">Actions</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <tr v-for="(item, index) in sap_table_field_options" :class="rowOptionColor(index)">
                                      <td class="pa-2">{{ index + 1 }}</td>

                                      <!-- START Show Data if row is not for edit (show by default) -->
                                      <template v-if="index !== editedOptionIndex && item.status !== 'New'">
                                        <td class="pa-2"> {{ item.value }} </td>
                                        <td class="pa-2"> {{ item.description }} </td>
                                      </template>
                                      <!-- END Show Data if row is not for edit (show by default) -->

                                      <!-- START Show Fields if row is for edit -->
                                      <template v-if="index === editedOptionIndex || item.status === 'New'">
                                        <td class="pa-2">
                                          <v-text-field
                                            name="value"
                                            v-model="editedOption.value"
                                            dense
                                            hide-details
                                            required
                                            :error-messages="optionValueErrors"
                                            @input="$v.editedOption.value.$touch()"
                                            @blur="$v.editedOption.value.$touch()"
                                            v-if="editedField.type === 'string' || editedField.type === ''"
                                          ></v-text-field>
                                          <v-menu
                                            ref="menu"
                                            class="pa-0"
                                            v-model="date_menu_option_value"
                                            :close-on-content-click="false"
                                            transition="scale-transition"
                                            offset-y
                                            min-width="auto"
                                            v-if="editedField.type === 'date'"
                                          >
                                            <template v-slot:activator="{ on, attrs }">
                                              <v-text-field
                                                class="pa-0 ma-0"
                                                v-model="computedOptionValueFormatted"
                                                prepend-icon="mdi-calendar"
                                                v-bind="attrs"
                                                v-on="on"
                                                hide-details=""
                                                :error-messages="optionValueErrors"
                                                @input="$v.editedOption.value.$touch()"
                                                @blur="$v.editedOption.value.$touch()"
                                              ></v-text-field>
                                            </template>
                                            <v-date-picker
                                              v-model="editedOption.value"
                                              no-title
                                              scrollable
                                              @input="date_menu_option_value = false"
                                            >
                                            </v-date-picker>
                                          </v-menu>
                                          <v-text-field-integer
                                            class="pa-0"
                                            v-model="editedOption.value"
                                            v-bind:properties="{
                                              name: 'length',
                                              placeholder: '0',
                                              'hide-details': true,
                                              dense: true,
                                              error: $v.editedOption.value.$error,
                                              messages: optionValueErrors,
                                            }"
                                            @input="$v.editedOption.value.$touch()"
                                            @blur="$v.editedOption.value.$touch()"
                                            v-if="editedField.type === 'integer'"
                                          >
                                          </v-text-field-integer>
                                          <v-text-field-money
                                            class="pa-0"
                                            v-model="editedOption.value"
                                            v-bind:properties="{
                                              name: 'length',
                                              placeholder: '0',
                                              'hide-details': true,
                                              dense: true,
                                              error: $v.editedOption.value.$error,
                                              messages: optionValueErrors,
                                            }"
                                            v-bind:options="{
                                              length: 11,
                                              precision: 2,
                                              empty: null,
                                            }"
                                            @input="$v.editedOption.value.$touch()"
                                            @blur="$v.editedOption.value.$touch()"
                                            v-if="editedField.type === 'decimal'"
                                          >
                                          </v-text-field-money>
                                        </td>
                                        <td class="pa-2">
                                          <v-text-field
                                            name="description"
                                            v-model="editedOption.description"
                                            dense
                                            hide-details
                                            required
                                            :error-messages="optionDescriptionErrors"
                                            @input="$v.editedOption.description.$touch()"
                                            @blur="$v.editedOption.description.$touch()"
                                          ></v-text-field>
                                        </td>
                                      </template>
                                      <!-- END Show Fields if row is for edit -->

                                      <!-- START Show Edit(pencil icon) and Delete (trash icon) button if not Edit Mode (show by default) -->
                                      <template v-if="index !== editedOptionIndex && item.status !== 'New' ">
                                        <td class="pa-2">
                                          <v-icon
                                            small
                                            class="mr-2"
                                            color="green"
                                            @click="editOption(item)"
                                            :disabled="tableOptionsMode === 'Add' ? true : false"
                                          >
                                            mdi-pencil
                                          </v-icon>

                                          <v-icon
                                            small
                                            color="red"
                                            @click="removeOptionRow(item)"
                                            :disabled="['Add', 'Edit'].includes(tableOptionsMode)"
                                          >
                                            mdi-delete
                                          </v-icon>
                                        </td>
                                      </template>
                                      <!-- END Show Edit(pencil icon) and Delete (trash icon) button if not Edit Mode (show by default) -->

                                      <!-- START  Show Save and Cancel button if Edit Mode -->
                                      <template v-if="index === editedOptionIndex ? true : false || item.status === 'New' ">
                                        <td class="pa-2">
                                          <v-btn
                                            x-small
                                            :disabled="disabled"
                                            @click="saveOption()"
                                            icon
                                          >
                                            <v-icon color="primary">mdi-content-save</v-icon>
                                          </v-btn>
                                          <v-btn
                                            x-small
                                            color="#E0E0E0"
                                            @click="cancelOptionEvent(item)"
                                            icon
                                          >
                                            <v-icon color="red">mdi-cancel</v-icon>
                                          </v-btn>
                                        </td>
                                      </template>
                                      <!-- END  Show Save and Cancel button if Edit Mode -->
                                    </tr>
                                  </tbody>
                                  <tfoot>
                                    <tr>
                                      <td colspan="4" class="text-right">
                                        <v-btn class="primary" x-small @click="newOptionItem()" :disabled="['Add', 'Edit'].includes(tableOptionsMode)">add item</v-btn>
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
                                v-if="optionListError.status || optionUnsaved || optionsValueInvalid"
                              >
                                {{ optionListError.errorMsg }}
                              </v-alert>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                    </v-card-text>
                    <v-divider class="mb-3 mt-0"></v-divider>
                    <v-card-actions class="pa-0">
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" :class="(mode === 'Edit' && editedItem.id) || mode === 'Add' ? 'mb-3' : 'mb-3 mr-6' ">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="saveUDFTable()"
                        class="mb-3 mr-6"
                        :disabled="disabled"
                        v-if="(mode === 'Edit' && editedItem.id) || mode === 'Add'"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="sap_tables"
            :search="search"
            :loading="loading"
            group-by="table_name"
            class="elevation-1"
            :expanded.sync="expanded"
            loading-text="Loading... Please wait"
          >
            <template
              v-slot:group.header="{
                items,
                headers,
                toggle,
                isOpen,
              }"
            >
              <td>
                <v-btn
                  @click="toggle"
                  small
                  icon
                  :ref="items"
                  :data-open="isOpen"
                >
                  <v-icon v-if="isOpen">mdi-chevron-up</v-icon>
                  <v-icon v-else>mdi-chevron-down</v-icon>
                </v-btn>
                {{ items[0].table_name }}
              </td>
              <td> {{ items[0].description }} </td>
              <td colspan="7"> {{ items[0].type }}  </td>
              <td> 
                <v-tooltip top :color=" items[0].is_migrated ? 'success' : '' ">
                  <template v-slot:activator="{ on, attrs }">
                    <v-icon :color=" items[0].is_migrated ? 'success' : '' " v-bind="attrs" v-on="on">
                      mdi-checkbox-marked-circle
                    </v-icon> 
                  </template>
                  <span>{{ items[0].is_migrated ? 'Migrated' : 'Not Migrated' }}</span>
                </v-tooltip>  
              </td> 
              <td>
                <v-icon
                  small
                  class="mr-2"
                  color="info"
                  @click="migrate('table', items[0])"
                  :disabled="items[0].is_migrated ? true : false"
                >
                  mdi-upload
                </v-icon>
                <v-icon
                  small
                  class="mr-2"
                  color="green"
                  @click="editUDFTable('Header', items[0])"
                >
                  mdi-pencil
                </v-icon>

                <v-icon
                  small
                  color="red"
                  @click="showConfirmAlert('Header', items[0])"
                >
                  mdi-delete
                </v-icon>
              </td>
            </template>
            <template v-slot:item="{ item }">
              <tr v-for="(value, index) in item.sap_table_fields">
                <td colspan="2"></td>
                <td> {{ value.type }} </td>
                <td> {{ value.field_name }} </td>
                <td> {{ value.description }} </td>
                <td> {{ value.length }} </td>
                <td> {{ value.default_value }} </td>
                <td>  <v-chip :color="value.is_required ? 'primary' : 'seconday'" small> {{ value.is_required ? 'Required' : 'Nullable' }} </v-chip></td>
                <td> <v-icon color="primary" v-if="value.has_options"> mdi-check </v-icon> </td>
                <td> 
                  <v-tooltip top :color=" value.is_migrated ? 'success' : '' ">
                    <template v-slot:activator="{ on, attrs }">
                      <v-icon :color=" value.is_migrated ? 'success' : '' " v-bind="attrs" v-on="on">
                        mdi-checkbox-marked-circle
                      </v-icon> 
                    </template>
                    <span>{{ value.is_migrated ? 'Migrated' : 'Not Migrated' }}</span>
                  </v-tooltip>
                </td>  
                <td>
                  <v-icon
                    small
                    class="mr-2"
                    color="info"
                    @click="migrate('field', value)"
                    :disabled="value.is_migrated ? true : false"
                  >
                    mdi-upload
                  </v-icon>
                  <v-icon
                    small
                    class="mr-2"
                    color="green"
                    @click="editUDFTable('Row', value)"
                  >
                    mdi-pencil
                  </v-icon>

                  <v-icon
                    small
                    color="red"
                    @click="showConfirmAlert('Row', value)"
                  >
                    mdi-delete
                  </v-icon>
                </td>
              </tr>
            </template>
          </v-data-table>
          <v-dialog v-model="dialog_migrate" max-width="500px" persistent>
            <v-card>
              <v-card-text>
                <v-container>
                  <v-row
                    class="fill-height"
                    align-content="center"
                    justify="center"
                    v-if="migrating"
                  >
                    <v-col class="subtitle-1 font-weight-bold text-center mt-4" cols="12">
                      Migrating Table Fields
                    </v-col>
                    <v-col cols="6">
                      <v-progress-linear
                        color="primary"
                        indeterminate
                        rounded
                        height="6"
                      ></v-progress-linear>
                    </v-col>
                  </v-row>
                </v-container>
              </v-card-text>
            </v-card>
          </v-dialog>
        </v-card>
      </v-main>
    </div>
  </div>
</template>
<style>
#sap_table_fields th, #sap_table_fields td { border:1px solid #dddddd; border-bottom:1px solid #dddddd;}
#sap_table_field_options th, #sap_table_field_options td { border:1px solid #dddddd; border-bottom:1px solid #dddddd;}
</style>
<script>

import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, requiredIf, email } from "vuelidate/lib/validators";
import { mapState } from "vuex";
import OptionsTable from './components/OptionsTable.vue';
export default {

  components: {
    OptionsTable
  },

  mixins: [validationMixin],

  validations: {
    editedItem: {
      table_name: { required },
      description: { required },
      type: { required },
      parent_table: { required: requiredIf(function () {
            return this.parentTableIsRequired;
          }),  
      },
    },
    editedField: { 
      field_name: { required: requiredIf(function () {
            return this.tableFieldsMode;
          }),  
      },
      description: { required: requiredIf(function () {
            return this.tableFieldsMode;
          }),  
      },
      type: { required: requiredIf(function () {
            return this.tableFieldsMode;
          }),  
      },
      length: { required: requiredIf(function () {
            return this.fieldLengthIsRequired;
          }),  
      },
    },
    editedOption: { 
      value: { required: requiredIf(function () {
            return this.tableOptionsMode;
          }),   
      },
      description: { required: requiredIf(function () {
            return this.tableOptionsMode;
          }),   
      },
    }
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Table Name", value: "table" },
        { text: "Description", value: "description" },
        { text: "Type", value: "type" },
        { text: "Field Name", value: "field_name" },
        { text: "Field Description", value: "description" },
        { text: "Length", value: "length" },
        { text: "Default Value", value: "default_value" },
        { text: "Required", value: "is_required" },
        { text: "Has Options", value: "has_options" },
        { text: "Migrated", value: "is_migrated" },
        { text: "Actions", value: "actions", sortable: false, width: "110px" },
      ],
      disabled: false,
      dialog: false,
      expanded: [],
      sap_tables: [],
      parent_tables: [],
      editedIndex: -1,
      editedFieldIndex: -1,
      editedOptionIndex: -1,
      editedItem: {
        table_name: "",
        description: "",
        type: "",
        sap_table_fields: [],
      },
      defaultItem: {
        table_name: "",
        description: "",
        type: "",
        sap_table_fields: [],
      },
      editedField: {
        field_name: "",
        description: "",
        type: "",
        length: "",
        default_value: "",
        has_options: false,
        is_required: false,
      },
      defaultField: {
        field_name: "",
        description: "",
        type: "",
        length: "",
        default_value: "",
        has_options: false,
        is_required: false,
      },
      editedOption: {
        value: "",
        description: "",
      },
      defaultOption: {
        value: "",
        description: "",
      },
      sap_table_fields: [],
      sap_table_field_options: [],
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/",
        },
        {
          text: "SAP UDF Lists",
          disabled: true,
        },
      ],
      table_types: [
        { type: 'Header', value: 'Header' },
        { type: 'Row', value: 'Row' },
      ],
      data_types: [
        { type: 'string', value: 'string' },
        { type: 'integer', value: 'integer' },
        { type: 'decimal', value: 'decimal' },
        { type: 'date', value: 'date' },
      ],
      loading: true,
      mode: "",
      tableFieldsMode: "",
      tableOptionsMode: "",
      fieldHasOptions: false,
      fieldUnsaved: false,
      optionUnsaved: false,
      optionsValueInvalid: false,
      errorFields: {
        table_name: "",
        field_name: "",
        value: "",
      },
      date_menu_default_value: false,
      date_menu_option_value: false,
      dialog_migrate: false,
      migrating: false,
    };
  },

  methods: {
    getSAPUDF() {
      this.loading = true;
      axios.get("/api/sap/udf/index").then(
        (response) => {

          let data = response.data;
          this.sap_tables = data.sap_tables;
          this.parent_tables = data.parent_tables;
          
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    migrate(type, item) {
      // console.log(item);
      this.dialog_migrate = true;
      this.migrating = true;
      let data = {};

      if (type === 'table')
      {
        data = {
          type: type,
          id: item.id,
          table_name : item.table_name,
          fields: item.sap_table_fields
        };
      }
      else
      {
        data = {
          type: type,
          id: item.id,
          field_name : item.field_name,
          options: item.sap_table_field_options
        };
      }
      
      axios.post('/api/sap/udf/migrate', data).then(
        (response) => {
          console.log(response);
          let data = response.data;
          if(data.success)
          {
            this.showAlert(data.success);
            this.getSAPUDF();
            this.dialog_migrate = false;
            this.migrating = false;
          }
          else
          {
            this.showErrorAlert(data.error);
            this.dialog_migrate = false;
            this.migrating = false;
          }
        },
        (error) => {
          console.log(error);
        }
      )
    },

    newSAPTable() {
      this.dialog = true;
      this.mode = "Add";
      this.newFieldItem();
    },

    saveUDFTable() {

      // this.tableFieldsMode has value ('Add', 'Edit') then set this.tableFieldsMode = true
      this.fieldUnsaved = this.tableFieldsMode ? true : false;

      // this.tableOptionsMode has value ('Add', 'Edit') then set this.optionUnsaved = true
      this.optionUnsaved = this.tableOptionsMode ? true : false;

      this.$v.editedItem.$touch();
      this.$v.editedField.$touch();
      this.$v.editedOption.$touch();

      // if sapTableError is false && if fieldListError.status not true &&
      if(!this.sapTableError && !this.fieldListError.status && !this.fieldUnsaved)
      { 
        if(this.mode === 'Add')
        {
          this.storeUDFTable();
        }
        else
        {
          this.updateUDFTable();
        }
      }
    },

    editUDFTable(type, item){

      this.resetData();
      
      this.dialog = true;
      this.mode = "Edit";

      if(type === 'Header')
      {
        this.editedIndex = this.sap_tables.indexOf(item);

        this.editedItem = Object.assign({}, item);
        this.sap_table_fields = item.sap_table_fields;
        
      }
      else
      {
        this.sap_table_fields.push(item);
        this.editField(item);
      }
      
    },

    updateUDFTable(){
      this.loading = true;
      this.disabled = true;
      axios.post('/api/sap/udf/update/'+this.editedItem.id, this.editedItem).then(
        (response) => {
          this.disabled = false;
          console.log(response.data);
          let data = response.data;

          if(data.success)
          {
            // this.sap_tables.push(data.sap_table);
            // this.getSAPUDF();
            this.showAlert(data.success);            
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
      );
    },

    removeUDFRow() {},

    deleteUDFTable(item) {
      const data = { sap_table_id: item.id };
      this.loading = true;
      axios.post("/api/sap/udf/delete", data).then(
        (response) => {
          this.loading = false;
          let data = response.data;

          if(data.success)
          {
            this.showAlert(data.success);
            let index = this.sap_tables.indexOf(item);
            this.sap_tables.splice(index, 1);
          }
          else
          {
            this.showErrorAlert(data.error);
          }
          
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    storeUDFTable() {
      this.loading = true;
      this.disabled = true;
      axios.post('/api/sap/udf/store', this.editedItem).then(
        (response) => {
          this.disabled = false;
          console.log(response.data);
          let data = response.data;

          if(data.success)
          {
            // this.sap_tables.push(data.sap_table);
            this.getSAPUDF();
            this.showAlert(data.success);
            this.resetData();
            this.dialog = false;
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

    resetData(){
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
      this.sap_table_fields = [];
      this.mode = "";
      this.resetFieldData();
      this.resetOptionData();
    },

    async newFieldItem() {
      this.resetFieldData();
      this.tableFieldsMode = "Add";

      let hasNew = false;
      
      this.sap_table_fields.forEach((value, index) => {
        if (value.status === "New") {
          hasNew = true;
        }
      });

      if (!hasNew) {
        await this.sap_table_fields.push({ status: "New", sap_table_field_options: [] });
      }
      
      // get the index of latest pushed data 
      this.editedFieldIndex =  await this.sap_table_fields.length - 1;

      // auto scroll down when adding an item
      await this.updateScrollSAPFields();

    },

    saveField(){

      // this.tableOptionsMode has value ('Add', 'Edit') then set this.optionUnsaved = true
      this.optionUnsaved = this.tableOptionsMode ? true : false;

      this.$v.editedField.$touch();
      this.$v.editedOption.$touch();

      // validate options value (sap_table_field_options)
      this.validateOptionList();
      
      // if editedField has no error && if optionListError (sap_table_field_options) has no error
      if(!this.fieldListError.status && !this.optionListError.status && !this.optionsValueInvalid)
      { 
        if(this.mode === 'Add')
        {
          if(this.tableFieldsMode === 'Add')
          {
            let index = this.sap_table_fields.indexOf({ status: 'New' }); 
            this.sap_table_fields.splice(index, 1);

            this.editedField = Object.assign(this.editedField, { sap_table_field_options: this.sap_table_field_options})
            this.sap_table_fields.push(this.editedField);
          }
          else
          {

            let default_value = this.editedField.default_value;
            this.editedField.default_value = this.dataIsInvalid(default_value) ? "" : default_value; // if data is valid then retain the current value else set into ""
            this.sap_table_fields[this.editedFieldIndex] = this.editedField;
          }

          this.editedItem.sap_table_fields = this.sap_table_fields;
          this.resetFieldData();
        }
        else
        {
          this.tableFieldsMode === 'Add' ? this.storeField() : this.updateField();

        }

      }
      
    },
    
    storeField() { 
      this.disabled = true;
      const data = Object.assign(this.editedField, { 
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

            // this.editedField = Object.assign(this.editedField, { sap_table_field_options: this.sap_table_field_options})
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

    updateField() {
      const data = Object.assign(this.editedField, { sap_tabe_field_options: this.sap_tabe_field_options })
      this.loading = true;
      this.disabled = true;
      axios.post('/api/sap/udf/update_field/'+this.editedField.id, this.editedField).then(
        (response) => {
          this.disabled = false;
          console.log(response.data);
          let data = response.data;

          if(data.success)
          { 
            this.sap_table_fields[this.editedFieldIndex] = data.sap_table_field;
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

    cancelFieldEvent(item) {
      this.editedFieldIndex = this.sap_table_fields.indexOf(item);
      if (this.tableFieldsMode === "Add") {
        this.sap_table_fields.splice(this.editedFieldIndex, 1);
      } 

      this.resetFieldData();
    },

    editField(item) {
      this.tableFieldsMode = "Edit";
      this.editedField = Object.assign({}, item);
      this.fieldHasOptions = item.has_options ? true : false;
      this.editedFieldIndex = this.sap_table_fields.indexOf(item);
      this.sap_table_field_options = item.sap_table_field_options;
    },

    removeFieldRow(item) {
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

    deleteField(item) {
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

    resetFieldData(){
      this.$v.editedField.$reset();
      this.editedField = Object.assign({}, this.defaultField);
      this.editedFieldIndex = -1;
      this.tableFieldsMode = "";
      this.fieldHasOptions = false;
      this.fieldUnsaved = false;
      this.sap_table_field_options = [];
    },

    async newOptionItem() {
      this.resetOptionData();
      this.tableOptionsMode = "Add";

      let hasNew = false;
      
      this.sap_table_field_options.forEach((value, index) => {
        if (value.status === "New") {
          hasNew = true;
        }
      });

      if (!hasNew) {
        await this.sap_table_field_options.push({ status: "New" });
      }

      // get the index of latest pushed data 
      this.editedOptionIndex =  await this.sap_table_field_options.length - 1;

      // auto scroll down when adding an item
      await this.updateScrollFieldOptions();

    },

    saveOption(){
      
      this.$v.editedOption.$touch();
      
      // if option value has no error and option value table has no error
      if(!this.optionListError.status)
      { 
        if(this.mode === 'Add')
        {
          if(this.tableOptionsMode === 'Add')
          {
            let index = this.sap_table_field_options.indexOf({ status: 'New' }); 
            this.sap_table_field_options.splice(index, 1);
            this.sap_table_field_options.push(this.editedOption);
          }
          else
          {
            this.sap_table_field_options[this.editedOptionIndex] = this.editedOption;
          }
          
          this.sap_table_fields[this.editedFieldIndex] = this.sap_table_field_options;
          this.sap_tables[this.editedIndex] = this.sap_table_fields;
          this.resetOptionData();
        }
        else
        {
          let has_options = this.sap_table_fields[this.editedFieldIndex].has_options;
          let editedHasOptions = this.editedField.has_options;

          // if has_options field is for update from false to true or 0 to 1 value
          if(has_options !== editedHasOptions)
          {
            if(this.tableOptionsMode === 'Add')
            {
              let index = this.sap_table_field_options.indexOf({ status: 'New' }); 
              this.sap_table_field_options.splice(index, 1);
              this.sap_table_field_options.push(this.editedOption);
            }
            else
            {
              this.sap_table_field_options[this.editedOptionIndex] = this.editedOption;
            }
            this.resetOptionData();
          }
          else
          { 
            this.tableOptionsMode === 'Add' ? this.storeOption() : this.updateOption();
          }
        }
      }
      
      // validate options value (sap_table_field_options)
      this.validateOptionList();
        
    },

    storeOption() {
     
      const data = Object.assign(this.editedOption, { 
        sap_table_field_id: this.editedField.id,
        field_type: this.editedField.type, 
      });

      this.disabled = true;

      axios.post('/api/sap/udf/store_option', data).then(
        (response) => {
          this.disabled = false;
          console.log(response);
          let data = response.data;

          if(data.success)
          {
            let index = this.sap_table_field_options.indexOf({ status: 'New' }); 
            this.sap_table_field_options.splice(index, 1);
            this.sap_table_field_options.push(data.sap_table_field_option);
            this.sap_table_fields[this.editedFieldIndex].sap_table_field_options = this.sap_table_field_options;
            console.log(this.sap_table_fields);
            this.showAlert(data.success)

            this.resetOptionData();
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

    updateOption() {
      this.disabled = true;
      this.loading = true;
      const data = Object.assign(this.editedOption, { field_type: this.editedField.type });
      axios.post('/api/sap/udf/update_option/'+this.editedOption.id, this.editedOption).then(
        (response) => {
          this.disabled = false;
          console.log(response.data);
          let data = response.data;

          if(data.success)
          { 
            this.sap_table_field_options[this.editedOptionIndex] = this.editedOption;
            this.getSAPUDF();
            this.showAlert(data.success);
            this.resetOptionData();            
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

    cancelOptionEvent(item) {
      this.editedOptionIndex = this.sap_table_field_options.indexOf(item);
      if (this.tableOptionsMode === "Add") {
        this.sap_table_field_options.splice(this.editedOptionIndex, 1);
      } 

      this.resetOptionData();
    },

    editOption(item) {
      let value = this.dataIsInvalid(item.value) ? "" : item.value;
      this.tableOptionsMode = "Edit";
      this.editedOption = Object.assign(item, { value: value });
      this.editedOptionIndex = this.sap_table_field_options.indexOf(item);
    },

    removeOptionRow(item) {
      let index = this.sap_table_field_options.indexOf(item);
      if(this.mode === 'Add')
      {
        this.sap_table_field_options.splice(index, 1);
      }
      else
      {
        this.showConfirmAlert('Option', item)
      }

    },

    deleteOption(item) {
      const data = { sap_table_field_option_id: item.id }

      this.loading = true;
      axios.post("/api/sap/udf/delete_option", data).then(
        (response) => {
   
          this.loading = false;
          let data = response.data;

          if(data.success)
          {
            this.showAlert(data.success)
            let index = this.sap_table_field_options.indexOf(item);

            this.sap_table_field_options.splice(index, 1);
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

    resetOptionData(){
      this.$v.editedOption.$reset();
      this.editedOption = Object.assign({}, this.defaultOption);
      this.editedOptionIndex = -1;
      this.tableOptionsMode = "";
      this.optionUnsaved = false;
    },

    hasOptionsClick(){
      // if field has options then call newOptionItem() function
      
      if(this.editedField.has_options)
      {
        this.fieldHasOptions = true;
        this.newOptionItem();
      }
      else
      { 

        // if has option list then show confirm dialog
        if(this.hasOptionList)
        {
            this.$swal({
              title: "Remove Field Options",
              text: "You are about to remove field options.",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "primary",
              cancelButtonColor: "#6c757d",
              confirmButtonText: "Procced!",
            }).then((result) => {
              if (result.value) {
                this.fieldHasOptions = false;
                this.sap_table_field_options = [];
                this.resetOptionData();
              }
              else
              {
                this.fieldHasOptions = true;
                this.editedField.has_options = true;
              }
            })
        }
        else
        {
          this.fieldHasOptions = false;
          this.sap_tabe_field_options = [];
          this.resetOptionData();
        }
        
      }
    },

    validateOptionList() {

      this.optionsValueInvalid = false;

      let invalid = false;     

      // validate if values from sap_table_field_options are valid depending on the sap table field type
      this.sap_table_field_options.forEach((value, index) => {

        if(!value.status){
          invalid = this.dataIsInvalid(value.value);
        }
        
      });

      this.optionsValueInvalid = invalid;
    },

    dataIsInvalid(value) {
      let type = this.editedField.type;
      let invalid = false;
      let spChars1 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/; //all special characters
      let spChars2 = /[!@#$%^&*()_+\-=\[\]{};':"\\|,<>\/?]+/; //all special characters whithout period/dot (.)

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
      return invalid;
    },

    updateScrollSAPFields() {
      var element = document.getElementById("sap_table_fields");
      element.scrollTop = element.scrollHeight;
    },

    updateScrollFieldOptions() {
      var element = document.getElementById("sap_table_field_options");
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

    showErrorAlert(msg) {
      this.$swal({
        position: "center", 
        icon: "error",
        title: "Error Occurred",
        text: msg,
        showConfirmButton: true,
        timer: 10000,
      });
    },

    showConfirmAlert(table, item) {
      this.$swal({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Delete record!",
      }).then((result) => {
        // <--

        if (result.value) {
          // <-- if confirmed
          
          if(table === 'Header')
          {
            this.deleteUDFTable(item);
          }
          else if(table === 'Row')
          {
            this.deleteField(item);
          }
          else
          {
            this.deleteOption(item);
          }
        }
      });
    },

    close() {
      this.dialog = false;
      this.resetData();
    },

    formatDate(date) {
      let timestamp = Date.parse(date);

      if (!date || isNaN(timestamp)) return null;
    
      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },

    rowFieldColor(index){
      // if edit mode then set the color of edited row into 'red lighten-5' or 'blue lighten-5' else ''
      return index === this.editedFieldIndex ? this.fieldListError.status ? 'red lighten-5' : 'blue lighten-5' : ''
    },

    rowOptionColor(index){

      let className = '';

      // if edit mode then set the color of edited row into 'red lighten-5' or 'blue lighten-5'
      if(index === this.editedOptionIndex)
      { 
        // if this.optionListError.status is true or this.optionValueExists is true then set 'red lighten-5' else 'blue lighten-5'
        className = this.optionListError.status || this.optionValueExists ? 'red lighten-5' : 'blue lighten-5';
      }
      else
      {
        // if this.optionsValueInvalid is true then set 'red lighten-5' else ''
        className = this.optionsValueInvalid ? 'red lighten-5' : '';
      }

      return className;

      // return index === this.editedOptionIndex ? this.optionListError.status || this.optionValueExists ? 'red lighten-5' : 'blue lighten-5' : this.optionsValueInvalid ? 'red lighten-5' : '';
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },

    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "sap-udf-create" ||
          action == "sap-udf-edit" ||
          action == "sap-udf-delete"
        ) {
          this.getSAPUDF();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? "New SAP Table Fields"
        : "Edit SAP Table Fields";
    },
    sapTableError() {
      return this.$v.editedItem.$error || this.tableNameIsInvalid;
    },
    tableNameErrors() {
      const errors = [];
    
      if (!this.$v.editedItem.table_name.$dirty) return errors;
      if (this.errorFields.table_name) errors.push(this.errorFields.table_name);
      if (this.tableNameIsInvalid) errors.push("Table Name must be alphanumeric only and starts with letter");
      !this.$v.editedItem.table_name.required && errors.push("Table Name is required.");
      return errors;
    },
    tableNameIsInvalid() {
      let spChars = /[!@#$%^&*()+\-=\[\]{};':"\\|,.<>\/?]+/; //exclude (_)
      let value = this.editedItem.table_name;

      // if value has special chars or value starts with number
      return spChars.test(value) || /^\d/.test(value);
    },
    tableTypeErrors() {
      const errors = [];
      if (!this.$v.editedItem.type.$dirty) return errors;
      !this.$v.editedItem.type.required && errors.push("Type is required.");
      return errors;
    },
    tableDescriptionErrors() {
      const errors = [];
      if (!this.$v.editedItem.description.$dirty) return errors;
      !this.$v.editedItem.description.required && errors.push("Description is required.");
      return errors;
    },
    parentTableErrors() {
      const errors = [];
      if (!this.$v.editedItem.parent_table.$dirty) return errors;
      !this.$v.editedItem.parent_table.required && errors.push("Parent Table is required.");
      return errors;
    },
    parentTableIsRequired() {
      return this.editedItem.type === "Row";
    },
    fieldNameErrors(){
      const errors = [];
      if (!this.$v.editedField.field_name.$dirty) return errors;
      if (this.errorFields.field_name) errors.push(this.errorFields.field_name);
      if (this.fieldNameExists) errors.push('Field Name exists');
      !this.$v.editedField.field_name.required && errors.push("Field Name is required.");
      return errors;
    },
    fieldDescriptionErrors(){
      const errors = [];
      if (!this.$v.editedField.description.$dirty) return errors;
      !this.$v.editedField.description.required && errors.push("Description is required.");
      return errors;
    },
    fieldTypeErrors(){
      const errors = [];
      if (!this.$v.editedField.type.$dirty) return errors;
      !this.$v.editedField.type.required && errors.push("Field Type is required.");
      return errors;
    },
    fieldLengthErrors(){
      const errors = [];
      if (!this.$v.editedField.length.$dirty) return errors;
      !this.$v.editedField.length.required && errors.push("Field Length is required.");
      return errors;
    },
    optionValueErrors(){
      const errors = [];
      if (!this.$v.editedOption.value.$dirty) return errors;
      if (this.errorFields.value) errors.push(this.errorFields.value);
      if (this.optionValueExists) errors.push('Option Value exists');
      !this.$v.editedOption.value.required && errors.push("Option Value is required.");
      return errors;
    },
    optionDescriptionErrors(){
      const errors = [];
      if (!this.$v.editedOption.description.$dirty) return errors;
      !this.$v.editedOption.description.required && errors.push("Description is required.");
      return errors;
    },
    fieldLengthIsRequired(){
      let required = false;
      if(this.editedField.type == 'string')
      {
        required =  true;
      }
      return required;
    },
    hasOptionList()
    {
      let hasOptionList = false;

      // scan sap_table_field_options if has option item
      this.sap_table_field_options.forEach(value => {
        if(!value.status)
        {
          hasOptionList = true;
        }
      });

      return hasOptionList;

    },
    fieldNameExists() {
      let hasError = false;
      this.sap_table_fields.forEach((value, index) => {
        if(this.editedField.field_name === value.field_name && this.editedFieldIndex !== index)
        {
          hasError = true;
        }
      });
      return hasError;
    },
    optionValueExists() {
      let hasError = false;
      this.sap_table_field_options.forEach((value, index) => {
        if(this.editedOption.value === value.value && this.editedOptionIndex !== index)
        {
          hasError = true;
        }
      });
      return hasError;
    },
    
    fieldListError(){
      let hasError = false;
      // if sap_table_fields has no data then set true
    
      let errorMsg = "";

      if(!this.sap_table_fields.length || this.$v.editedField.$error)
      {
        errorMsg = "Table Fields are required!"
        hasError = true;
      }
      else if(this.fieldNameExists)
      {
        errorMsg = "Field Name exists!"
        hasError = true;
      }
      else if(this.fieldUnsaved)
      {
        errorMsg = "There are unsaved data!"
      }

      return { status: hasError, errorMsg: errorMsg };
    },
    optionListError(){

      //if field has options and sap_table_field_options is empty then set error to true || option value exists || options table value invalid
      let hasError = false;
      // hasError = this.editedField.has_options && !this.sap_table_field_options.length ? true : this.optionValueExists ? true : this.optionsValueInvalid;
      let errorMsg = "";

      if(this.$v.editedField.$error)
      {
        errorMsg = "Option Value and Description are required!"
        hasError = true;
      }
      else if(this.editedField.has_options && !this.sap_table_field_options.length)
      {
        errorMsg = "Options list is required!"
        hasError = true;
      }
      else if(this.optionValueExists)
      {
        errorMsg = "Option Value exists!"
        hasError = true;
      }
      else if(this.optionsValueInvalid)
      {
        errorMsg = "Options value is invalid! Must be type " + this.editedField.type;
      }
      else if(this.optionUnsaved)
      {
        errorMsg = "There are unsaved data!"
      }

      return { status: hasError, errorMsg: errorMsg };
    },
    computedDefaultValueFormatted() {
      return this.formatDate(this.editedField.default_value);
    },
    computedOptionValueFormatted() {
      return this.formatDate(this.editedOption.value);
    },
    
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  watch: {
    "editedField.type"() {
      
      if(this.editedField.type !== 'string') { this.editedField.length = ""; }
      
      let default_value = this.editedField.default_value;
      this.editedField.default_value = this.dataIsInvalid(default_value) ? "" : default_value;

      // validate options value (sap_table_field_options)
      this.validateOptionList();
    }
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getSAPUDF();
    // this.websocket();

  },
};
</script>