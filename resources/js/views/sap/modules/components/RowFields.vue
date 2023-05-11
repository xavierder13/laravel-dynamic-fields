<template>
  <div>
     
      <!-- <template v-if="has_options">
        <v-autocomplete
          class="pa-0"
          :name="field_name + '[]'"
          :items="options"
          v-model="field_value"
          item-text="description"
          item-value="value"
          required
          dense
          hide-details
        >
          <template slot="selection" slot-scope="data">
            {{ data.item.value + ' - ' + data.item.description }}
          </template>
          <template slot="item" slot-scope="data">
            {{ data.item.value + ' - ' + data.item.description }}
          </template>
        </v-autocomplete>
      </template> -->

     
      <template v-if="!has_options">
        
        <v-text-field
          class="pa-0 ma-0"
          :name="field_name + '[]'"
          v-model="field_value"
          dense
          hide-details
          v-if="type === 'string'"
        ></v-text-field>

       
        <v-menu
          ref="menu"
          class="pa-0"
          v-model="date_menu"
          :close-on-content-click="false"
          transition="scale-transition"
          offset-y
          min-width="auto"
          v-if="type === 'date'"
        >
          <template v-slot:activator="{ on, attrs }">
            <v-text-field
              :name="field_name + '[]'"
              v-model="formattedDate"
              class="pa-0 ma-0"
              prepend-icon="mdi-calendar"
              v-bind="attrs"
              v-on="on"
              hide-details=""
            ></v-text-field>
          </template>
          <v-date-picker
            v-model="field_value"
            no-title
            scrollable
            @input="formatDateValue(field_name)"
          >
          </v-date-picker>
        </v-menu>

        <v-text-field-integer
          class="pa-0"
          v-model="field_value"
          v-bind:properties="{
            name: field_name + '[]',
            placeholder: '0',
            'hide-details': true,
            dense: true,
          }"
          v-if="type === 'integer'"
        >
        </v-text-field-integer>

        <v-text-field-money
          class="pa-0"
          v-model="field_value"
          v-bind:properties="{
            name: field_name + '[]',
            placeholder: '0',
            'hide-details': true,
            dense: true,
          }"
          v-bind:options="{
            length: 11,
            precision: 2,
            empty: null,
          }"
          v-if="type === 'decimal'"
        >
        </v-text-field-money>
      </template>
  </div>
</template>
<script>

export default {
  name: "RowFields",
  props: {
    field_name: String,
    field_value: String,
    description: String,
    has_options: Boolean,
    options: Array,
    date_menu: Boolean,
    type: String,
  },
  data () {
    return {

    }
  },

  methods: {
    formatDateValue(table_name, i) {
      let value = this.editedItem[table_name].data[i].value;
      this.editedItem[table_name].data[i].formatted_date = this.formatDate(value);
 
    },
    formatDate(date) {
      let timestamp = Date.parse(date);

      if (!date || isNaN(timestamp)) return null;
    
      const [year, month, day] = date.split("-");
      return `${month}/${day}/${year}`;
    },
  },

  computed: {
    formattedDate() {
      return this.formatDate(this.field_value);
    }
  },

  mounted() {

  }
}
</script>
