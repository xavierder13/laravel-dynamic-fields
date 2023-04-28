import Vue from 'vue';
import Vuex from 'vuex';
import auth from './modules/auth';
import userRolesPermissions from './modules/userRolesPermissions';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    auth,
    userRolesPermissions
  }
}) 