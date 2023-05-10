<template>
  <v-app>
    <!-- Navbar -->
    <v-app-bar dense dark app>
      <v-btn icon @click.stop="drawer = !drawer">
        <v-app-bar-nav-icon></v-app-bar-nav-icon>
      </v-btn>
      <v-spacer></v-spacer>

      <v-menu offset-y :nudge-width="200">
        <template v-slot:activator="{ on, attrs }">
          <v-btn small rounded v-bind="attrs" v-on="on" color="grey darken-3">
            <v-icon> mdi-menu-down </v-icon>
          </v-btn>
        </template>
        <v-card color="grey lighten-3">
          <v-card-text class="text-center">
            <v-row>
              <v-col
                ><img
                  src="/img/default-profile.png"
                  width="120px"
                  height="100px"
                  alt="User"
                  style="border-radius: 50%"
              /></v-col>
            </v-row>
            <v-row>
              <v-col>
                <h5 class="text--secondary">
                  {{ user.name }}
                </h5>
                <h6 class="text--disabled">
                  {{
                    user.id === 1
                      ? "Administrator"
                      : user.position
                      ? user.position.name
                      : ""
                  }}
                </h6>
              </v-col>
            </v-row>
          </v-card-text>
          <v-divider class="pa-0 mb-0"></v-divider>
          <v-card-actions class="grey darken-2 d-flex justify-content-around">
            <div>
              <v-btn class="white--text" plain small @click="userProfile()">
                <v-icon small class="mr-1">mdi-account</v-icon> Profile
              </v-btn>
            </div>
            <div>
              <v-btn class="white--text" plain small @click="confirmLogout()">
                <v-icon small class="mr-1">mdi-logout</v-icon> Logout
              </v-btn>
            </div>
          </v-card-actions>
        </v-card>
      </v-menu>
    </v-app-bar>

    <!-- Sidebar -->
    <v-navigation-drawer v-model="drawer" dark app>
      <v-list>
        <v-list-item class="px-2">
          <v-list-item-avatar class="rounded-5" height="60" width="60">
            <v-img src="/img/default-profile.png"></v-img>
          </v-list-item-avatar>
          <v-list-item-content>
            <v-list-item-title>Laravel Dynamic Fields</v-list-item-title>
            <v-list-item-subtitle>{{ user.name }}</v-list-item-subtitle>
          </v-list-item-content>
        </v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list>
        <v-list-item link to="/dashboard">
          <v-list-item-icon>
            <v-icon>mdi-view-dashboard</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Dashboard</v-list-item-title>
        </v-list-item>
        <v-list-group
          no-action
          v-if="hasPermission('user-list') || hasPermission('user-create')"
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-account-arrow-right-outline</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>User</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/user/index" v-if="hasPermission('user-list')">
            <v-list-item-content>
              <v-list-item-title>User Record</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item link to="/user/create" v-if="hasPermission('user-create')">
            <v-list-item-content>
              <v-list-item-title>Create New</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-group no-action>
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-database-plus</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>SAP Modules</v-list-item-title>
            </v-list-item-content>
          </template>
          <template v-for="(item, i) in modules">
            <v-list-item link :to="('/sap/module/') + item.id">
              <v-list-item-content>
                <v-list-item-title> {{ item.description }} </v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </template>
          
        </v-list-group>
        <v-list-group no-action>
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-database-plus</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>SAP UDF</v-list-item-title>
            </v-list-item-content>
          </template>
          <v-list-item link to="/sap/udf/index">
            <v-list-item-content>
              <v-list-item-title>UDF List</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-group
          no-action
          v-if="
            hasPermission('role-list') ||
            hasPermission('role-create') ||
            hasPermission('permission-list') ||
            hasPermission('permission-create')
          "
        >
          <!-- List Group Icon-->
          <v-icon slot="prependIcon">mdi-cog</v-icon>
          <!-- List Group Title -->
          <template v-slot:activator>
            <v-list-item-content>
              <v-list-item-title>Settings</v-list-item-title>
            </v-list-item-content>
          </template>
          <!-- List Group Items -->
          <v-list-item link to="/role/index" v-if="hasPermission('role-list')">
            <v-list-item-content>
              <v-list-item-title>Role</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
          <v-list-item
            link
            to="/permission/index"
            v-if="hasPermission('permission-list')"
          >
            <v-list-item-content>
              <v-list-item-title>Permission</v-list-item-title>
            </v-list-item-content>
          </v-list-item>
        </v-list-group>
        <v-list-item link to="/activity_logs" v-if="hasPermission('activity-logs')">
          <v-list-item-icon>
            <v-icon>mdi-history</v-icon>
          </v-list-item-icon>
          <v-list-item-title>Activity Logs</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>
    <v-overlay :absolute="absolute" :value="overlay">
      <v-progress-circular
        :size="70"
        :width="7"
        color="primary"
        indeterminate
      ></v-progress-circular>
    </v-overlay>
    <!-- Content -->
    <router-view />

    <v-footer padless dense dark app>
      <v-col class="text-center" cols="12">
        Copyright © {{ new Date().getFullYear() }} —
        <strong> LARAVEL DYNAMIC FIELDS</strong>
      </v-col>
    </v-footer>
  </v-app>
</template>

<style>
  html { overflow-y: auto !important } /* show scrollbar when overflow */
</style>

<script>

import axios from "axios";
import { mapState, mapActions, mapGetters } from "vuex";

export default {
  data() {
    return {
      absolute: true,
      overlay: false,
      drawer: true,
      mini: false,
      right: null,
      selectedItem: 1,
      loading: null,
      initiated: false,
      modules: [],
    };
  },

  methods: {
    getSAPModules() {
      axios.get('/api/sap/modules').then(
        (response) => {
          this.modules = response.data.modules;
        },
        (error) => {
          console.log(error);
        }
      )
    },
    redirect(id) {
      this.$router.push({
        name: "sap.module",
        params: { sap_table_id: id },
      });
    },
    userProfile() {
      this.$router.push({ name: "user.profile" }).catch((e) => {});
    },
    websocket() {
      // Socket.IO fetch data
      this.$options.sockets.sendData = (data) => {
        let action = data.action;
        if (
          action == "user-edit" ||
          action == "role-edit" ||
          action == "role-delete" ||
          action == "permission-delete"
        ) {
          this.userRolesPermissions();
        }
      };
    },
    logout() {
      this.overlay = true;
      axios.get("/api/auth/logout").then(
        (response) => {
          if (response.data.success) {
            this.overlay = false;

            // remove all local storage including access_token
            window.localStorage.clear();
            this.$router.push("/login").catch(() => {});
          }
        },
        (error) => {
          this.overlay = false;
          console.log(error);

          // if unauthenticated (401)
          if (error.response.status == "401") {

            // remove all local storage including access_token
            window.localStorage.clear();
            this.$router.push({ name: "login" });
          }
        }
      );
    },

    confirmLogout() {
      
      this.$swal({
        title: "Log Out",
        text: "Are you sure you want to log out?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "primary",
        cancelButtonColor: "secondary",
        confirmButtonText: "Log Out",
      }).then((result) => {
        
        if (result.value) {
          this.overlay = true;
          this.logout();
        }
        
      });

    },

    sessionExpiredSwal(){
      this.$swal({
          title: "Session Expired",
          text: "You have been inactive for 30 Minutes(s)",
          showCancelButton: false,
          confirmButtonText: "Ok",
      });

      // this.logout();
    },
    ...mapActions("auth", ["getUser"]),
    ...mapActions("userRolesPermissions", ["userRolesPermissions"]),

  },

  computed: {
    isIdle() {
			return this.$store.state.idleVue.isIdle;
		},
    ...mapState("auth", ["user"]),
    ...mapGetters("userRolesPermissions", ["hasRole", "hasPermission"]),
  },
  
  mounted() {
    axios.defaults.headers.common["Authorization"] =
      "Bearer " + localStorage.getItem("access_token");
    this.userRolesPermissions();
    this.getUser();
    this.getSAPModules();
    // this.websocket();

  },

};
</script>