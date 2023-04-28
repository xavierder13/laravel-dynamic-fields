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
          <v-card-title>
            User Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              v-if="userPermissions.user_list"
            ></v-text-field>
            <template>
              <v-toolbar flat>
                <v-spacer></v-spacer>
                <v-btn
                  color="primary"
                  fab
                  dark
                  class="mb-2"
                  @click="clear() + (dialog = true)"
                  v-if="userPermissions.user_create"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>

                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
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
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="email"
                              v-model="editedItem.email"
                              :error-messages="emailErrors + userError.email"
                              label="E-mail"
                              @input="$v.editedItem.email.$touch() + (userError.email = [])"
                              @blur="$v.editedItem.email.$touch()"
                              :readonly="
                                emailReadonly || editedItem.id == 1
                                  ? true
                                  : false
                              "
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="password"
                              v-model="password"
                              :error-messages="passwordErrors + userError.password"
                              label="Password"
                              required
                              @input="$v.password.$touch() + (userError.password = [])"
                              @blur="$v.password.$touch() + dummyPassword"
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
                              :readonly="editedItem.id == 1 ? true : false"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-text-field
                              name="confirm_password"
                              v-model="confirm_password"
                              :error-messages="confirm_passwordErrors + userError.confirm_password"
                              label="Confirm Password"
                              required
                              @input="$v.confirm_password.$touch() + (userError.confirm_password = [])"
                              @blur="
                                $v.confirm_password.$touch() + dummyPassword
                              "
                              @keyup="passwordChange()"
                              @focus="onFocus()"
                              type="password"
                              :readonly="editedItem.id == 1 ? true : false"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                        <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-autocomplete
                              v-model="editedItem.roles"
                              :items="roles"
                              item-text="name"
                              item-value="name"
                              label="Roles"
                              multiple
                              chips
                              :readonly="editedItem.id == 1 ? true : false"
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
                            <v-switch
                              v-model="switch1"
                              :label="activeStatus"
                              :readonly="editedItem.id == 1 ? true : false"
                            ></v-switch>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                    <v-divider class="mb-3 mt-0"></v-divider>
                    <v-card-actions class="pa-0">
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" :class="editedItem.id != 1 ? 'mb-3' : 'mb-3 mr-4'">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="save"
                        :disabled="disabled"
                        class="mb-3 mr-4"
                        v-if="editedItem.id != 1"
                      >
                        Save
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
                <v-dialog
                  v-model="dialogPermission"
                  max-width="700px"
                  persistent
                >
                  <v-card>
                    <v-card-title class="mb-0 pb-0">
                      <span class="headline">Roles</span>
                      <v-spacer></v-spacer>
                      <v-text-field
                        v-model="search_roles_permissions"
                        append-icon="mdi-magnify"
                        label="Search"
                        single-line
                      ></v-text-field>
                      <v-spacer></v-spacer>
                      <v-icon @click="closeRolesBreakdown()">mdi-close</v-icon>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-data-table
                          :headers="roles_permissions_headers"
                          :items="rolesBreakdown"
                          :search="search_roles_permissions"
                          :loading="loading"
                          item-key="index"
                          group-by="role"
                          class="elevation-1"
                          :expanded.sync="expanded"
                          loading-text="Loading... Please wait"
                          fixed-header
                        >
                          <template
                            v-slot:group.header="{
                              items,
                              headers,
                              toggle,
                              isOpen,
                            }"
                          >
                            <td :colspan="headers.length">
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
                              <v-chip color="secondary">
                                <strong>{{ items[0].role }}</strong>
                              </v-chip>
                            </td>
                          </template>
                        </v-data-table>
                        <!-- <v-row>
                          <v-col class="mt-0 mb-0 pt-0 pb-0">
                            <v-expansion-panels>
                              <v-expansion-panel
                                v-for="(role, i) in roles_permissions"
                                :key="i"
                              >
                                <v-expansion-panel-header>
                                  {{ role.name }}
                                </v-expansion-panel-header>
                                <v-expansion-panel-content>
                                  <v-chip
                                    color="secondary"
                                    v-for="(permission, i) in role.permissions"
                                    :key="i"
                                    class="ma-1"
                                  >
                                    {{ permission.name }}
                                  </v-chip>
                                </v-expansion-panel-content>
                              </v-expansion-panel>
                            </v-expansion-panels>
                          </v-col>
                        </v-row> -->
                      </v-container>
                    </v-card-text>
                    <v-card-actions>
                      <v-spacer></v-spacer>
                    </v-card-actions>
                  </v-card>
                </v-dialog>
              </v-toolbar>
            </template>
          </v-card-title>
          <v-data-table
            :headers="headers"
            :items="users"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="userPermissions.user_list"
          >
            <template v-slot:item.roles="{ item }">
              <span v-for="(role, key) in item.roles">
                <v-chip
                  small
                  color="secondary"
                  v-if="key == 0"
                  @click="viewRolesBreakdown(item.roles)"
                >
                  {{ role.name }}
                </v-chip>

                <v-chip
                  small
                  v-if="key == 0 && item.roles.length > 1"
                  @click="viewRolesBreakdown(item.roles)"
                >
                  {{ "+" }}
                  {{
                    key == 0 && item.roles.length > 1
                      ? item.roles.length - 1
                      : role.name
                  }}
                  {{ "others" }}
                </v-chip>
              </span>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editUser(item)"
                v-if="userPermissions.user_edit && item.id != 1"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="userPermissions.user_delete && item.id != 1"
              >
                mdi-delete
              </v-icon>
              <v-icon
                small
                color="info"
                @click="editUser(item)"
                v-if="item.id == 1"
              >
                mdi-eye
              </v-icon>
            </template>
          </v-data-table>
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

import { mapState } from 'vuex';  

export default {

  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
      email: { required, email },
    },
    password: { required, minLength: minLength(8) },
    confirm_password: { required, sameAsPassword: sameAs("password") },
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
          text: "Users Record",
          disabled: true,
        },
      ],
      search: "",
      search_roles_permissions: "",
      headers: [
        { text: "Full Name", value: "name" },
        { text: "E-mail", value: "email" },
        { text: "Active", value: "active" },
        { text: "Last Login", value: "last_login" },
        { text: "Roles", value: "roles" },
        { text: "Actions", value: "actions", sortable: false, width: "80px" },
      ],
      roles_permissions_headers: [
        { text: "Role", value: "role" },
        { text: "Permission", value: "permission" },
      ],
      expanded: [],
      switch1: true,
      disabled: false,
      emailReadonly: false,
      dialog: false,
      dialogPermission: false,
      users: [],
      roles: [],
      roles_permissions: [],
      editedIndex: -1,
      editedItem: {
        name: "",
        email: "",
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
      password: "",
      confirm_password: "",
      loading: true,
      passwordHasChanged: false,
      userError: {
        name: [],
        email: [],
        password: [],
        confirm_password: [],
      }
    };
  },

  methods: {
    getUser() {
      this.loading = true;
      axios.get("/api/user/index").then(
        (response) => {
          this.users = response.data.users;
          this.roles = response.data.roles;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editUser(item) {
      this.editedIndex = this.users.indexOf(item);
      this.editedItem = Object.assign({}, item);
      this.dialog = true;
      this.emailReadonly = true;
      this.password = "password";
      this.confirm_password = "password";
      if (item.active == "Y") {
        this.switch1 = true;
      } else {
        this.switch1 = false;
      }
    },

    deleteUser(user_id) {
      const data = { user_id: user_id };

      axios.post("/api/user/delete", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Sockot.IO Server
            this.$socket.emit("sendData", { action: "user-delete" });
          }
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

    showConfirmAlert(item) {
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

          const user_id = item.id;
          const index = this.users.indexOf(item);

          //Call delete User function
          this.deleteUser(user_id);

          //Remove item from array services
          this.users.splice(index, 1);

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

    close() {
      this.dialog = false;
      this.clear();
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
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
        let roles = [];

        this.editedItem.roles.forEach((value) => {
          // if value is object with role name
          if (value.name) {
            roles.push(value.name);
          } else {
            // else get the array of role name
            roles.push(value);
          }
        });

        this.editedItem.roles = roles;

        if (this.editedIndex > -1) {
          if (this.passwordHasChanged) {
            this.editedItem.password = this.password;
            this.editedItem.confirm_password = this.confirm_password;
          }

          const data = this.editedItem;
          const user_id = this.editedItem.id;

          axios.post("/api/user/update/" + user_id, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                this.$socket.emit("sendData", { action: "user-edit" });

                Object.assign(this.users[this.editedIndex], response.data.user);
                this.showAlert();
                this.close();
              }
              else 
              {
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
        } else {
          this.editedItem.password = this.password;
          this.editedItem.confirm_password = this.confirm_password;

          const data = this.editedItem;

          axios.post("/api/user/store", data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                this.$socket.emit("sendData", { action: "user-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.users.push(response.data.user);
              }
              else 
              {
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
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem.email = "";
      this.emailReadonly = false;
      this.password = "";
      this.confirm_password = "";
      this.editedItem.active = "Y";
      this.passwordHasChanged = false;
      this.switch1 = true;
    },
    onFocus() {
      if (this.editedIndex > -1 && this.editedItem.id != 1) {
        if (!this.passwordHasChanged) {
          this.password = "";
          this.confirm_password = "";
        }
      }
    },
    passwordChange() {
      if (this.password || this.confirm_password) {
        this.passwordHasChanged = true;
      } else {
        this.passwordHasChanged = false;
      }
    },
    viewRolesBreakdown(roles) {
      this.dialogPermission = true;
      this.roles_permissions = roles;
    },

    closeRolesBreakdown() {
      this.dialogPermission = false;
      this.search_roles_permissions = "";
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
          action == "user-create" ||
          action == "user-edit" ||
          action == "user-delete"
        ) {
          this.getUser();
        }
      };
    },

  },
  computed: {
    formTitle() {
      return this.editedIndex === -1
        ? "New User"
        : this.editedItem.id === 1
        ? "User"
        : "Edit User";
    },
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
      if (!this.$v.password.$dirty) return errors;
      !this.$v.password.required && errors.push("Password is required.");
      !this.$v.password.minLength &&
        errors.push("Password must be atleast 8 characters.");
      return errors;
    },

    confirm_passwordErrors() {
      const errors = [];
      if (!this.$v.confirm_password.$dirty) return errors;
      !this.$v.confirm_password.required &&
        errors.push("Password Confirmation is required.");
      !this.$v.confirm_password.sameAsPassword &&
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
    dummyPassword() {
      if (this.editedIndex > -1) {
        if (!this.password && !this.confirm_password) {
          this.password = "password";
          this.confirm_password = "password";
        }
      }
    },
    rolesBreakdown() {
      let roles_permissions = [];
      let index = 0;
      this.roles_permissions.forEach((value) => {
        value.permissions.forEach((val) => {
          roles_permissions.push({
            index: index,
            role: value.name,
            permission: val.name,
          });
          index++;
        });
      });

      return roles_permissions;
    },
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] = "Bearer " + localStorage.getItem("access_token");
    this.getUser();
    this.websocket();
  },
};
</script>