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
            Permission Lists
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              append-icon="mdi-magnify"
              label="Search"
              single-line
              v-if="hasPermission('permission-list')"
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
                  v-if="hasPermission('permission-create')"
                >
                  <v-icon>mdi-plus</v-icon>
                </v-btn>
                <v-dialog v-model="dialog" max-width="500px" persistent>
                  <v-card>
                    <v-card-title>
                      <span class="headline">{{ formTitle }}</span>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                      <v-container>
                        <v-row>
                          <v-col>
                            <v-text-field
                              name="permission"
                              v-model="editedPermission.name"
                              label="Permission"
                              required
                              :error-messages="permissionErrors + permissionError.name"
                              @input="$v.editedPermission.name.$touch() + (permissionError.name = [])"
                              @blur="$v.editedPermission.name.$touch()"
                            ></v-text-field>
                          </v-col>
                        </v-row>
                      </v-container>
                    </v-card-text>
                    <v-divider class="mb-3 mt-0"></v-divider>
                    <v-card-actions class="pa-0">
                      <v-spacer></v-spacer>
                      <v-btn color="#E0E0E0" @click="close" class="mb-3">
                        Cancel
                      </v-btn>
                      <v-btn
                        color="primary"
                        @click="save"
                        class="mb-3 mr-4"
                        :disabled="disabled"
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
            :items="permissions"
            :search="search"
            :loading="loading"
            loading-text="Loading... Please wait"
            v-if="hasPermission('permission-list')"
          >
            <template v-slot:item.actions="{ item }">
              <v-icon
                small
                class="mr-2"
                color="green"
                @click="editPermission(item)"
                v-if="hasPermission('permission-edit')"
              >
                mdi-pencil
              </v-icon>
              <v-icon
                small
                color="red"
                @click="showConfirmAlert(item)"
                v-if="hasPermission('permission-delete')"
              >
                mdi-delete
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
import { required, maxLength, email } from "vuelidate/lib/validators";
import { mapState, mapGetters } from 'vuex';

export default {

  mixins: [validationMixin],

  validations: {
    editedPermission: {
      name: { required },
    },
  },
  data() {
    return {
      search: "",
      headers: [
        { text: "Permission", value: "name" },
        { text: "Actions", value: "actions", sortable: false, width: "80px" },
      ],
      disabled: false,
      dialog: false,
      permissions: [],
      editedIndex: -1,
      editedPermission: {
        name: "",
      },
      defaultItem: {
        name: "",
      },
      items: [
        {
          text: "Home",
          disabled: false,
          link: "/",
        },
        {
          text: "Permission Lists",
          disabled: true,
        },
      ],
      loading: true,
      permissionError: {
        name: [],
      }
    };
  },

  methods: {
    getPermission() {
      this.loading = true;
      axios.get("/api/permission/index").then(
        (response) => {
          this.permissions = response.data.permissions;
          this.loading = false;
        },
        (error) => {
          this.isUnauthorized(error);
        }
      );
    },

    editPermission(item) {
      this.editedIndex = this.permissions.indexOf(item);
      this.editedPermission = Object.assign({}, item);
      this.dialog = true;
    },

    deletePermission(permissionid) {
      const data = { permissionid: permissionid };
      this.loading = true;
      axios.post("/api/permission/delete", data).then(
        (response) => {
          if (response.data.success) {
            // send data to Sockot.IO Server
            this.$socket.emit("sendData", { action: "permission-delete" });
          }
          this.loading = false;
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

          const permissionid = item.id;
          const index = this.permissions.indexOf(item);

          //Call delete Permission function
          this.deletePermission(permissionid);

          //Remove item from array permissions
          this.permissions.splice(index, 1);

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
        this.editedPermission = Object.assign({}, this.defaultItem);
        this.editedIndex = -1;
      });
    },

    save() {

      this.$v.$touch();
      this.permissionError = {
        name: []
      };

      if (!this.$v.$error) {
        this.disabled = true;

        if (this.editedIndex > -1) {
          const data = this.editedPermission;
          const permissionid = this.editedPermission.id;

          axios.post("/api/permission/update/" + permissionid, data).then(
            (response) => {
              if (response.data.success) {
                // send data to Sockot.IO Server
                this.$socket.emit("sendData", { action: "permission-edit" });

                Object.assign(
                  this.permissions[this.editedIndex],
                  this.editedPermission
                );
                this.showAlert();
                this.close();

              }
              else
              {
                let errors = response.data;
                let errorNames = Object.keys(response.data);

                errorNames.forEach(value => {
                  this.permissionError[value].push(errors[value]);
                });
              }

              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        } else {
          const data = this.editedPermission;

          axios.post("/api/permission/store", data).then(
            (response) => {
              
              if (response.data.success) {
                // send data to Sockot.IO Server
                this.$socket.emit("sendData", { action: "permission-create" });

                this.showAlert();
                this.close();

                //push recently added data from database
                this.permissions.push(response.data.permission);
              }
              else
              { 
                let errors = response.data;
                let errorNames = Object.keys(response.data);

                errorNames.forEach(value => {
                  this.permissionError[value].push(errors[value]);
                });
                
              }
              this.disabled = false;
            },
            (error) => {
              this.isUnauthorized(error);
              this.disabled = false;
            }
          );
        }
      }
    },

    clear() {
      this.$v.$reset();
      this.editedPermission.name = "";
      this.permissionError = {
        name: []
      };
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
          action == "permission-create" ||
          action == "permission-edit" ||
          action == "permission-delete"
        ) {
          this.getPermission();
        }
      };
    },
  },
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? "New Permission" : "Edit Permission";
    },
    permissionErrors() {
      const errors = [];
      if (!this.$v.editedPermission.name.$dirty) return errors;
      !this.$v.editedPermission.name.required &&
        errors.push("Permission is required.");
      return errors;
    },
    ...mapGetters("userRolesPermissions", ["hasRole", "hasPermission"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.getPermission();
    // this.websocket();
  },
};
</script>