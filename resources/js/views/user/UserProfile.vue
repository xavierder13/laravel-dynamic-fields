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
            <span class="headline">My Profile</span>
          </v-card-title>
          <v-divider></v-divider>
          <v-card-text class="ml-4">
            <v-row>
              <v-col cols="4" class="my-0 py-0">
                <v-text-field
                  name="name"
                  v-model="editedItem.name"
                  :error-messages="nameErrors + userError.name"
                  label="Full Name"
                  @input="$v.editedItem.name.$touch() + (userError.name = [])"
                  @blur="$v.editedItem.name.$touch()"
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="my-0 py-0">
                <v-text-field
                  name="email"
                  v-model="editedItem.email"
                  label="E-mail"
                  readonly
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="my-0 py-0">
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
                ></v-text-field>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="4" class="my-0 py-0">
                <v-text-field
                  name="confirm_password"
                  v-model="confirm_password"
                  :error-messages="
                    confirm_passwordErrors + userError.confirm_password
                  "
                  label="Confirm Password"
                  required
                  @input="
                    $v.confirm_password.$touch() +
                      (userError.confirm_password = [])
                  "
                  @blur="$v.confirm_password.$touch() + dummyPassword"
                  @keyup="passwordChange()"
                  @focus="onFocus()"
                  type="password"
                ></v-text-field>
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
import { mapState } from "vuex";
export default {
  mixins: [validationMixin],

  validations: {
    editedItem: {
      name: { required },
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
          link: "/",
        },
        {
          text: "My Profile",
          disabled: true,
        },
      ],
      switch1: true,
      disabled: false,
      editedIndex: -1,
      editedItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        roles: [],
        active: "Y",
        branch_id: "",
        position_id: "",
      },
      defaultItem: {
        name: "",
        email: "",
        password: "",
        confirm_password: "",
        roles: [],
        active: "Y",
        branch_id: "",
        position_id: "",
      },
      userError: {
        name: [],
        email: [],
        password: [],
        confirm_password: [],
        branch_id: [],
        position_id: [],
      },
      passwordHasChanged: false,
      password: "password",
      confirm_password: "password",
    };
  },

  watch: {
    userIsLoaded: {
      handler() {
        if (this.userIsLoaded) {
          this.editedItem.name = this.user.name;
          this.editedItem.email = this.user.email;
        }
      },
    },
  },

  methods: {
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

        if (this.passwordHasChanged) {
          this.editedItem.password = this.password;
          this.editedItem.confirm_password = this.confirm_password;
        } else {
          this.editedItem.password = "";
          this.editedItem.confirm_password = "";
        }

        const data = this.editedItem;
        const user_id = this.user.id;

        axios.post("/api/user/update_profile/" + user_id, data).then(
          (response) => {
            if (response.data.success) {
              // send data to Sockot.IO Server
              // this.$socket.emit("sendData", { action: "user-edit" });

              this.showAlert();

              this.passwordHasChanged = false;
            } else {
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

    onFocus() {
      if (!this.passwordHasChanged) {
        this.password = "";
        this.confirm_password = "";
      }
    },

    passwordChange() {
      if (this.password || this.confirm_password) {
        this.passwordHasChanged = true;
      } else {
        this.passwordHasChanged = false;
      }
    },
    clear() {
      this.$v.$reset();
      this.editedItem = Object.assign({}, this.defaultItem);
      this.switch1 = true;
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
    dummyPassword() {
      if (!this.password && !this.confirm_password) {
        this.password = "password";
        this.confirm_password = "password";
      }
    },
    ...mapState("auth", ["user", "userIsLoaded"]),
    ...mapState("userRolesPermissions", ["userRoles", "userPermissions"]),
  },
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");

    this.editedItem.name = this.user.name;
    this.editedItem.email = this.user.email;
  },
};
</script>