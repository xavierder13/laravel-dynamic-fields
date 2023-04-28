<template>
  <div>
    <v-card>
      <v-card-title class="subtitle-1">Field Options</v-card-title>
      <v-card-text>
        <v-simple-table class="elevation-1" id="sap_table_field_options">
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
              <tr v-for="(item, index) in sap_table_field_options" :class="index === editedOptionIndex ? 'blue lighten-5' : ''">
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
                    ></v-text-field>
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
                      @click="showConfirmAlert('Options', item)"
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
      </v-card-text>
    </v-card>
  </div>
</template>

<script>
import axios from "axios";
import { validationMixin } from "vuelidate";
import { required, requiredIf } from "vuelidate/lib/validators";
import { mapState } from "vuex";
export default {
  
  name: "OptionsTable",
  props: {

  },

  mixins: [validationMixin],
  validations: {
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
  data () {
    return {
      editedFieldIndex: -1,
      editedOptionIndex: -1,
      editedOption: {
        value: "",
        description: "",
      },
      defaultOption: {
        value: "",
        description: "",
      },
      sap_table_field_options: [],
      tableOptionsMode: "",
    }
  },
  methods: {

    newOptionItem() {
      this.resetOptionData();
      this.tableOptionsMode = "Add";

      let hasNew = false;
      
      this.sap_table_field_options.forEach((value, index) => {
        if (value.status === "New") {
          hasNew = true;
        }
      });

      if (!hasNew) {
        this.sap_table_field_options.push({ status: "New" });
      }

    },

    saveOption(){
      
      this.$v.editedOption.$touch();

      if(!this.$v.editedOption.$error)
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
    },

    cancelOptionEvent(item) {
      this.editedOptionIndex = this.sap_table_field_options.indexOf(item);
      if (this.tableOptionsMode === "Add") {
        this.sap_table_field_options.splice(this.editedOptionIndex, 1);
      } 

      this.resetOptionData();
    },

    editOption(item) {
      this.tableOptionsMode = "Edit";
      this.editedOption = Object.assign({}, item);
      this.editedOptionIndex = this.sap_table_field_options.indexOf(item);
    },

    deleteOption(id) {
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

    resetOptionData(){
      this.$v.editedOption.$reset();
      this.editedOption = Object.assign({}, this.defaultField);
      this.editedOptionIndex = -1;
      this.tableOptionsMode = "";
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
    optionValueErrors(){
      const errors = [];
      if (!this.$v.editedOption.value.$dirty) return errors;
      !this.$v.editedOption.value.required && errors.push("Option Value is required.");
      return errors;
    },
    optionDescriptionErrors(){
      const errors = [];
      if (!this.$v.editedOption.description.$dirty) return errors;
      !this.$v.editedOption.description.required && errors.push("Description is required.");
      return errors;
    },
  },
  mounted(){
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
  }
  
};

</script>