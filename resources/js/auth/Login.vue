<template>
  <v-app v-if="!user">
    <v-main>
      <v-container fluid fill-height>
        <v-layout align-center justify-center>
          <v-flex xs12 sm8 md4>
            <v-overlay :absolute="absolute" :value="overlay">
              <v-progress-circular
                :size="70"
                :width="7"
                color="primary"
                indeterminate
              ></v-progress-circular>
            </v-overlay>
            <v-card class="elevation-12">
              <v-toolbar color="dark" dark flat>
                <v-toolbar-title color>Login</v-toolbar-title>
                <v-spacer></v-spacer>
              </v-toolbar>
              <v-card-text>
                <v-alert dense outlined type="error" v-if="isInvalid">
                  Invalid Credentials
                </v-alert>
                <v-form class="mr-5 ml-5">
                  <v-text-field
                    label="Email"
                    name="email"
                    type="text"
                    v-model="email"
                    prepend-icon="mdi-account"
                    required
                    :error-messages="emailErrors"
                    @change="$v.email.$touch()"
                    @blur="$v.email.$touch()"
                    @keyup="isInvalid = false"
                  ></v-text-field>

                  <v-text-field
                    id="password"
                    label="Password"
                    name="password"
                    type="password"
                    v-model="password"
                    prepend-icon="mdi-key"
                    required
                    :error-messages="passwordErrors"
                    @change="$v.password.$touch()"
                    @blur="$v.password.$touch()"
                    @keyup="isInvalid = false"
                  ></v-text-field>
                </v-form>
              </v-card-text>
              <v-card-actions class="mr-5 ml-5">
                <v-btn color="primary" class="mb-5 mr-5" block @click="login"
                  >Login</v-btn
                >
              </v-card-actions>
            </v-card>
          </v-flex>
        </v-layout>
      </v-container>
    </v-main>
  </v-app>
</template>

<script>
import Axios from "axios";
import { validationMixin } from "vuelidate";
import { required, maxLength, email } from "vuelidate/lib/validators";

export default {
  name: "login",
  mixins: [validationMixin],

  validations: {
    email: { required },
    password: { required },
  },
  data() {
    return {
      email: "",
      password: "",
      user: null,
      isInvalid: false,
      absolute: true,
      overlay: false,
    };
  },
  computed: {
    emailErrors() {
      const errors = [];
      if (!this.$v.email.$dirty) return errors;
      // !this.$v.lastname.maxLength &&
      // errors.push("Name must be at most 10 characters long");
      !this.$v.email.required && errors.push("Email is required.");
      return errors;
    },

    passwordErrors() {
      const errors = [];
      if (!this.$v.password.$dirty) return errors;
      !this.$v.password.required && errors.push("Password is required.");
      return errors;
    },
  },
  methods: {
    login() {
      this.$v.$touch();

      if (!this.$v.$error) {
        this.overlay = true;
        const email = this.email;
        const password = this.password;
        const data = { email: email, password: password };

        Axios.post("/api/auth/login", data).then(
          (response) => {
            if (response.data.access_token) {
              localStorage.setItem("access_token", response.data.access_token);
              this.$router.push("/").catch((e) => {});
              this.clear();
            } else {
              this.isInvalid = true;
              this.overlay = false;
            }
          },
          (error) => {
            console.log(error);
          }
        );
      }
    },

    clear() {
      this.$v.$reset();
      this.isInvalid = false;
      this.email = null;
      this.password = null;
      this.overlay = false;
    },
  },

  mounted() {
    let self = this;
    window.addEventListener("keyup", function (event) {
      if (event.keyCode === 13) {
        self.login();
      }
    });
  },
};
</script>