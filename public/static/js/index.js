var myVue = new Vue({
  el: '.myVue',
  data: {
  	s1: false,
  	s2: true,
  	img1: "/static/img/tg_icon_ptdl_default@2x.png",
  	img2: "/static/img/tg_icon_jpdl_pressed@2x.png",
    amount: 1,
	  mask: false
  },
  methods:{
  	click1:function(){
  		myVue.img1 = "/static/img/tg_icon_ptdl_pressed@2x.png";
  		myVue.img2 = "/static/img/tg_icon_jpdl_default@2x.png";
  		myVue.s1 = true;
  		myVue.s2 = false;
      myVue.amount = 0.01;
  	},
  	click2:function(){
  		myVue.img1 = "/static/img/tg_icon_ptdl_default@2x.png";
  		myVue.img2 = "/static/img/tg_icon_jpdl_pressed@2x.png";
  		myVue.s1 = false;
  		myVue.s2 = true;
      myVue.amount = 1;
  	},
    pay_bt:function(){
      myVue.mask = true;
    },
    hide_me:function(){
      myVue.mask = false;
    }
  }
  
  
})
