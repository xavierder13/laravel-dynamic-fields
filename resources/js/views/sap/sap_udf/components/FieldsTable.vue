<template>
  <div>
    <v-card>
      <v-card-title class="subtitle-1">Table Fields</v-card-title>
      <v-card-text>
        <v-simple-table class="elevation-1" id="sap_table_fields" style="max-height: 250px; overflow-y: scroll; overflow-y: auto !important">
          <template v-slot:default>
            <thead>
              <tr>
                <th class="pa-2" width="10px">#</th>
                <th class="pa-2">Field Name</th>
                <th class="pa-2">Description</th>
                <th class="pa-2" width="120px">Type</th>
                <th class="pa-2" width="60px" v-if="fieldLengthIsRequired">Length</th>
                <th class="pa-2">Default Value</th>
                <th class="pa-2">Has Options</th>
                <th class="pa-2">Required</th>
                <th class="pa-2" width="80px"> Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in sap_table_fields" :class="index === editedFieldIndex ? 'blue lighten-5' : ''">
                <td class="pa-2"> {{ index + 1 }} </td>

                <!-- START Show Data if row is not for edit (show by default) -->
                <template v-if="index !== editedFieldIndex && item.status !== 'New'">
                  <td class="pa-2"> {{ item.field_name }} </td>
                  <td class="pa-2"> {{ item.description }}</td>
                  <td class="pa-2"> {{ item.type }} </td>
                  <td class="pa-2" v-if="fieldLengthIsRequired"> {{ item.length }} </td>
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
                      @input="$v.editedField.field_name.$touch()"
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
                  <td class="pa-2" v-if="fieldLengthIsRequired">
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
                    ></v-text-field>
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
                      @click="showConfirmAlert('Row', item)"
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
                <td colspan="9" class="text-right">
                  <v-btn class="primary" x-small @click="newFieldItem()" :disabled="['Add', 'Edit'].includes(tableFieldsMode)">add item</v-btn>
                </td>
              </tr>
            </tfoot>
          </template>
        </v-simple-table>
      </v-card-text>
    </v-card>
  </div>
</template>
<script>

import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, requiredIf, email } from "vuelidate/lib/validators";

export default {

  name: "FieldsTable",
  props: {

  },

  mixins: [validationMixin],

  validations: {
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
  },
  data() {
    return {
      editedFieldIndex: -1,
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
      sap_table_fields: [],
      data_types: [
        { type: 'string', value: 'string' },
        { type: 'integer', value: 'integer' },
        { type: 'decimal', value: 'decimal' },
        { type: 'date', value: 'date' },
      ],
      tableFieldsMode: "",
      fieldHasOptions: false,
    };
  },

  methods: {

    newFieldItem() {
      this.resetFieldData();
      this.tableFieldsMode = "Add";

      let hasNew = false;
      
      this.sap_table_fields.forEach((value, index) => {
        if (value.status === "New") {
          hasNew = true;
        }
      });

      let data = { status: "New" };

      if (!hasNew) {
        this.sap_table_fields.push({ status: "New", sap_table_field_options: [] });
      }

    },

    saveField(){
      console.log(this.editedField);
      this.$v.editedField.$touch();

      if(!this.$v.editedField.$error)
      {
        if(this.tableFieldsMode === 'Add')
        {
          let index = this.sap_table_fields.indexOf({ status: 'New' }); 
          this.sap_table_fields.splice(index, 1);
          this.sap_table_fields.push(this.editedField);
        }
        else
        {
          this.sap_table_fields[this.editedFieldIndex] = this.editedField;
        }
        this.sap_tables[this.editedIndex] = this.sap_table_fields;
        this.resetFieldData();
      }
      
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
      this.fieldHasOptions = item.has_options;
      this.editedField = Object.assign({}, item);
      this.editedFieldIndex = this.sap_table_fields.indexOf(item);
      this.sap_table_field_options = item.sap_table_field_options;
    },

    deleteField(id) {
      const data = { roleid: roleid };
      this.loading = true;
      axios.post("/api/role/delete", data).then(
        (response) => {
          this.loading = false;
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
      this.sap_table_field_options = [];
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

        let hasOptionList = false;

        // scan sap_table_field_options if has option item
        this.sap_table_field_options.forEach(value => {
          if(!value.status)
          {
            hasOptionList = true;
          }
        });

        // if has option list then show confirm dialog
        if(hasOptionList)
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
                this.sap_table_field_options = [];
              }
              else
              {
                this.fieldHasOptions = true;
                this.editedField.has_options = true;
              }
            })
        }
        
      }
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

    showConfirmAlert(item, table) {
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

          const roleid = item.id;
          const index = this.roles.indexOf(item);

          //Call delete Role function
          this.deleteRole(roleid);

          //Remove item from array permissions
          this.roles.splice(index, 1);

          this.$swal({
            position: "center",
            icon: "success",
            title: "Record has been deleted",
            showConfirmButton: false,
            timer: 2500,
          });
        }
      });
    },

  },
  computed: {
    
    fieldNameErrors(){
      const errors = [];
      if (!this.$v.editedField.field_name.$dirty) return errors;
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
    fieldLengthIsRequired(){
      let required = false;
      if(this.editedField.type == 'string')
      {
        required =  true;
      }
      return required;
    },
  },
  watch: {
   
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
  },
};
</script>
