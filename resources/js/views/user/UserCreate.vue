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
                <v-text-field
                  name="name"
                  v-model="editedItem.name"
                  :error-messages="nameErrors + userError.name"
                  label="Full Name"
                  @input="$v.editedItem.name.$touch() + (userError.name = [])"
                  @blur="$v.editedItem.name.$touch()"
                  :readonly="editedItem.id == 1 ? true : false"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="email"
                  v-model="editedItem.email"
                  :error-messages="emailErrors + userError.email"
                  label="E-mail"
                  @input="$v.editedItem.email.$touch() + (userError.email = [])"
                  @blur="$v.editedItem.email.$touch()"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="password"
                  v-model="editedItem.password"
                  :error-messages="passwordErrors + userError.password"
                  label="Password"
                  required
                  @input="$v.editedItem.password.$touch() + (userError.password = [])"
                  @blur="$v.editedItem.password.$touch()"
                  type="password"
                  :readonly="editedItem.id == 1 ? true : false"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-text-field
                  name="confirm_password"
                  v-model="editedItem.confirm_password"
                  :error-messages="confirm_passwordErrors + userError.confirm_password"
                  label="Confirm Password"
                  required
                  @input="$v.editedItem.confirm_password.$touch() + (userError.confirm_password = [])"
                  @blur="$v.editedItem.confirm_password.$touch()"
                  type="password"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="mt-0 mb-0 pt-0 pb-0">
                <v-autocomplete
                  v-model="editedItem.roles"
                  :items="roles"
                  item-text="name"
                  item-value="name"
                  label="Roles"
                  multiple
                  chips
                >
                  <template v-slot:selection="data">
                    <v-chip
                      color="secondary"
                      v-bind="data.attrs"
                      :input-value="data.selected"
                      @click="data.select"
                    >
                      {{ data.item.name }}
                    </v-chip>
                  </template>
                </v-autocomplete>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="2" class="mt-0 mb-0 pt-0 pb-0">
                <v-switch v-model="switch1" :label="activeStatus"></v-switch>
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
      switch1: true,
      disabled: false,
      users: [],
      roles: [],
      roles_permissions: [],
      editedIndex: -1,
      editedItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        roles: [],
        active: "Y",
      },
      defaultItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        roles: [],
        active: "Y",
      },
      userError: {
        name: [],
        email: [],
        password: [],
        confirm_password: [],
      }

    };
  },

  methods: {
    getRole() {
      axios.get("/api/user/create").then(
        (response) => {
          this.roles = response.data.roles;
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
              this.$socket.emit("sendData", { action: "user-create" });

              this.showAlert();
              this.clear();

              //push recently added data from database
              this.users.push(response.data.user);
            }
            else {
              let errors = response.data;
              let errorNames = Object.keys(response.data);

              errorNames.forEach((value) => {
                this.userError[value].push(errors[value]);
              });
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
      this.switch1 = true;
      this.userError = {
        name: [],
        email: [],
        password: [],
        confirm_password: [],
      }
    },

    isUnauthorized(error) {
      // if unauthenticated (401)
      if (error.response.status == "401") {
        this.$router.push({ name: "unauthorize" });
      }
    },
  },
  computed: {
    nameErrors() {
      const errors = [];
      if (!this.$v.editedItem.name.$dirty) return errors;
      !this.$v.editedItem.name.required && errors.push("Name is required.");
      return errors;
    },
    emailErrors() {
      const errors = [];
      if (!this.$v.editedItem.email.$dirty) return errors;
      !this.$v.editedItem.email.required && errors.push("Email is required.");
      !this.$v.editedItem.email.email && errors.push("Must be valid e-mail");
      return errors;
    },
    passwordErrors() {
      const errors = [];
      if (!this.$v.editedItem.password.$dirty) return errors;
      !this.$v.editedItem.password.required &&
        errors.push("Password is required.");
      !this.$v.editedItem.password.minLength &&
        errors.push("Password must be atleast 8 characters.");
      return errors;
    },

    confirm_passwordErrors() {
      const errors = [];
      if (!this.$v.editedItem.confirm_password.$dirty) return errors;
      !this.$v.editedItem.confirm_password.required &&
        errors.push("Password Confirmation is required.");
      !this.$v.editedItem.confirm_password.sameAsPassword &&
        errors.push("Passwords did not match");
      return errors;
    },

    activeStatus() {
      if (this.switch1) {
        this.editedItem.active = "Y";
        return " Active";
      } else {
        this.editedItem.active = "N";
        return " Inactive";
      }
    },
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] = "Bearer " + localStorage.getItem("access_token");
    this.getRole();
  },
};
</script>