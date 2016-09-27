import {inject} from 'aurelia-framework';
import {EventAggregator} from 'aurelia-event-aggregator';

import {Api} from 'api';
import {CrossfilterCreated, FilterUpdated, DimensionAdded} from 'topics';


@inject(Api, EventAggregator)
export class Taxon {

  loading = true;
  hiddenTaxaCount = 0;

  DURATION_MS = 500

  _chart = null;
  _cf = null;
  _dim = null; // crossfilter dimension
  _grp = null; // crossfilter group

  disabledTaxaNum = 0;

  constructor(api, ea) {
    this.api = api;
    this.ea = ea;
  }

  created() {
    this.subscribe();
  }

  attached() {
    let that = this;
    nv.addGraph(function () {
      let chart =   that._chart = nv.models.pieChart()
      .x(function (d) { return d.label })
      .y(function (d) { return d.value })
      .showLabels(true)
      .labelThreshold(.05)
      .labelType('percent')
      .donut(true)
      .donutRatio(0.35);
      chart.options({ legendPosition : 'right' });
      chart.margin({ left: 5, right: 5, top: 5, bottom: 5 });
      chart.dispatch.on('stateChange', evt => {
        setTimeout(() => that.onTaxonStateChange(evt), this.DURATION_MS);
      });
      return chart;
    });
  }

  onTaxonStateChange(event) {
    // the event.disabled property is a list of boolean values for
    // which taxe are active vs inactive.
    let data = this._grp.all();
    let disabled = event.disabled; // array of true/false
    let disabledTaxa = {};
    this.disabledTaxaNum = 0;
    for (var i = 0; i < disabled.length; i++) {
      let taxon = data[i].key;
      let b = disabled[i];
      disabledTaxa[taxon] = b;
      if(b) {
        this.disabledTaxaNum ++;
      }
    }
    this._dim.filter(null); // filters are additive per dimension.
    this._dim.filter( d => ! disabledTaxa[d] );
    this.ea.publish(new FilterUpdated(this));
  }

  subscribe() {
    this.ea.subscribe(CrossfilterCreated, msg => {
      this._cf = msg.crossfilter;
      this.setupCrossfilter();
      this.update();
    });
    this.ea.subscribe(FilterUpdated, msg => {
      if(msg.sender != this) {
        this.update();
      }
    });
  }

  setupCrossfilter() {
    // create a dimension for the taxon
    this._dim = this._cf.dimension(
      d => d.species ? d.genus + ' ' + d.species : ''
    );
    this._grp = this._dim.group();
  }

  update() {
    this.loading = false;

    // get the current results from our group
    let data = this._grp.all();
    // filter out taxa having 0 value (count)
    data = _.filter(data, d => d.value > 0 );
    // transform to objects expected by nvd3 chart
    data = _.map(data, d => {
      return { label: d.key, value: d.value };
    });
    this.display(data);
  }

  display(data) {
    d3.select(this.taxonSvgEl)
      .datum(data)
      .transition().duration(this.DURATION_MS)
      .call(this._chart);
    this._chart.legend.updateState();
  }

  onClearSelection() {
    this.disabledTaxaNum = 0;
    this._dim.filter(null);
    this.ea.publish(new FilterUpdated(this));
    this.update();
  }

};