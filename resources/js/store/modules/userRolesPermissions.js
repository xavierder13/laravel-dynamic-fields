
import axios from 'axios';
import router from '../../router';

const state = {
  permissions: [],
  roles: [],
  userRolesPermissionsIsLoaded: false,
};

const getters = {
  hasRole: (state) => (...role) => {
    return role.every(value => state.roles.includes(value));
  },

  hasAnyRole: (state) => (...role) => {
    return role.some(value => state.roles.includes(value));
  },

  hasPermission: (state) => (...permission) => {
    return permission.every(value => state.permissions.includes(value));
  },

  hasAnyPermission: (state) => (...permission) => {
    return permission.some(value => state.permissions.includes(value));
  },
};

const actions = {
  async userRolesPermissions({ commit }) {
    let response = await axios.get("/api/user/roles_permissions").then((response) => {
      commit('setUserRoles', response.data.user_roles);
      commit('setUserPermissions', response.data.user_permissions);

    },
      (error) => {
        if (error.response.status == "401") {
          router.push({ name: "unauthorize" });
        }
      });

  },

};

const mutations = {
  setUserRoles(state, roles) {
    state.roles = roles;
  },
  setUserPermissions(state, permissions) {
    state.permissions = permissions;
    // set true if user roles and permissions value successfully assigned
    state.userRolesPermissionsIsLoaded = true;
  },

};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations,
}
