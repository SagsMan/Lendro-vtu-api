//const { useEffect } = require("react");

const Services = ({services,wallet,Per5Point,setPage,popupOpen,setPopupOpen,setBusy}) => {
  const categories = services?.categories || [];
  const airtimes   = services?.airtime    || [];
  const mdatas     = services?.data       || [];

    return e("div", { className: "app-page pb-5" },
            e(inHeader,{pgtitle:"Partner Services",setPage,pg:"home"}),

            e("div", { className: "app-content px-3 pb-5 mt-6 space-y-6" },

              e("div",{ className: "bg-white rounded-xl shadow-sm border border-gray-100 p-3 mb-3" },

                  //Airtime.......
                  e("h3", { className: "text-gray-900 text-left border-b border-gray-20 font-bold pb-1 mb-3" }, "Airtime Recharge"),
                  e("div", { className: "grid grid-cols-4 gap-4 mb-5" },
                    // Use DB airtimes if available, otherwise static fallback for all 4 networks
                    (airtimes.filter(c=>!["foreign-airtime"].includes(c.serviceID)).length > 0
                      ? airtimes.filter(c=>!["foreign-airtime"].includes(c.serviceID)).map((airtime,i)=>{
                          let aName=airtime?.name||"", aCode=airtime?.serviceID||"", aImg=flwImages[aCode]||"", aMaxPrice=airtime?.maximum_amount||null, aServiceId=airtime?.id||null;
                          return (aName&&aCode)&&e("div",{key:aCode||i,className:"flex flex-col items-center gap-2 cursor-pointer",
                            onClick:()=>setPopupOpen({open:true,data:{dwat:"buyairtime",biller:aName,code:aCode,maxPrice:aMaxPrice,serviceid:aServiceId}})},
                            e("div",{className:`w-14 h-14 ${flwColors[aCode]||"bg-indigo-500"} rounded-xl flex items-center justify-center shadow-md`},
                              e("img",{className:"w-7 h-7 text-white rounded-full object-cover",src:aImg})
                            ),
                            e("span",{className:"text-xs text-center leading-tight"},e("p",{className:"font-semibold text-gray-900"},flwNames[aName]||aName),e("p",{className:"text-gray-500"},"Airtime"))
                          );
                        })
                      : [
                          {code:"mtn",    name:"MTN",     color:"bg-yellow-400"},
                          {code:"airtel", name:"Airtel",  color:"bg-red-500"},
                          {code:"glo",    name:"GLO",     color:"bg-green-600"},
                          {code:"9mobile",name:"9mobile", color:"bg-green-700"},
                        ].map((n,i)=>
                          e("div",{key:n.code,className:"flex flex-col items-center gap-2 cursor-pointer",
                            onClick:()=>setPopupOpen({open:true,data:{dwat:"buyairtime",biller:n.name,code:n.code,maxPrice:null,serviceid:null}})},
                            e("div",{className:`w-14 h-14 ${n.color} rounded-xl flex items-center justify-center shadow-md`},
                              flwImages[n.code]
                                ? e("img",{className:"w-8 h-8 rounded-full object-cover",src:flwImages[n.code]})
                                : e("i",{"data-lucide":"smartphone",className:"w-7 h-7 text-white"})
                            ),
                            e("span",{className:"text-xs text-center leading-tight"},e("p",{className:"font-semibold text-gray-900"},n.name),e("p",{className:"text-gray-500"},"Airtime"))
                          )
                        )
                    )
                  ),

                  //Data..........
                  e("h3", { className: "text-gray-900 text-left border-b border-gray-20 font-bold pb-1 mb-3" }, "Data Bundle"),
                  e("div", { className: "grid grid-cols-4 gap-4 mb-5" },
                    // Use DB data networks if available, otherwise static fallback with all 4 networks
                    (mdatas.length > 0
                      ? mdatas.map((mdata,i)=>{
                          let aName=mdata?.name||"", aCode=mdata?.serviceID||"", aImg=flwImages[aCode]||"", aMaxPrice=mdata?.maximum_amount||null;
                          return (aName&&aCode)&&e("div",{key:aCode||i,className:"flex flex-col items-center gap-2 cursor-pointer",
                            onClick:()=>{setPopupOpen({open:false,data:{dwat:"viewdataitems",catname:"data",code:aCode,maxPrice:aMaxPrice}});setPage("pageitems");}},
                            e("div",{className:`w-14 h-14 ${flwColors[aCode]||"bg-indigo-500"} rounded-xl flex items-center justify-center shadow-md`},
                              e("img",{className:"w-7 h-7 text-white rounded-full object-cover",src:aImg})
                            ),
                            e("span",{className:"text-xs text-center leading-tight"},e("p",{className:"font-semibold text-gray-900"},flwNames[aCode]||aName),e("p",{className:"text-gray-500"},"Data"))
                          );
                        })
                      : [
                          {code:"mtn-data",    net:"mtn",     label:"MTN",     color:"bg-amber-500"},
                          {code:"airtel-data",  net:"airtel",  label:"Airtel",  color:"bg-red-500"},
                          {code:"glo-data",     net:"glo",     label:"GLO",     color:"bg-green-600"},
                          {code:"9mobile-data", net:"9mobile", label:"9mobile", color:"bg-lime-600"},
                        ].map((n,i)=>
                          e("div",{key:n.code,className:"flex flex-col items-center gap-2 cursor-pointer",
                            onClick:()=>{setPopupOpen({open:false,data:{dwat:"viewdataitems",catname:"data",code:n.code,maxPrice:null}});setPage("pageitems");}},
                            e("div",{className:`w-14 h-14 ${n.color} rounded-xl flex items-center justify-center shadow-md`},
                              flwImages[n.net]
                                ? e("img",{className:"w-8 h-8 rounded-full object-cover",src:flwImages[n.net]})
                                : e("i",{"data-lucide":"wifi",className:"w-7 h-7 text-white"})
                            ),
                            e("span",{className:"text-xs text-center leading-tight"},e("p",{className:"font-semibold text-gray-900"},n.label),e("p",{className:"text-gray-500"},"Data"))
                          )
                        )
                    )
                  ),

                  //Other Services........
                  e("h3", { className: "text-gray-900 text-left border-b border-gray-20 font-bold pb-1 mb-3" }, "Other Services"),
                  e("div", { className: "grid grid-cols-4 gap-4 mb-5" },
                    (()=>{
                      const excluded = ["airtime","data","other-services"];
                      const fromAPI  = (categories||[]).filter(c=>c?.name&&c?.identifier).filter(c=>!excluded.includes(c.identifier));
                      // Hardcoded fallbacks — always show these even if not in DB
                      const fallbacks = [
                        {name:"Cable TV",   identifier:"tv-subscription"},
                        {name:"Electricity",identifier:"electricity-bill"},
                        {name:"Education",  identifier:"education"},
                      ];
                      const apiCodes = new Set(fromAPI.map(c=>c.identifier));
                      const merged   = [...fromAPI, ...fallbacks.filter(f=>!apiCodes.has(f.identifier))];
                      return merged.map((category,i)=>{
                        let catName=category?.name||"", catCode=category?.identifier||"";
                        return (catName&&catCode) && e("div",{key:catCode||i,className:"flex flex-col items-center gap-2 cursor-pointer",
                          onClick:()=>{setPopupOpen({open:false,data:{dwat:"viewcatitems",catname:catName,code:catCode}});setPage("pageitems");}},
                          e("div",{className:`w-14 h-14 ${flwColors[catCode]||"bg-yellow-500"} rounded-xl flex items-center justify-center shadow-md`},
                            e("i",{"data-lucide":`${flwIcons[catCode]||"smartphone"}`,className:"w-7 h-7 text-white"})
                          ),
                          e("span",{className:"text-xs text-center leading-tight"},e("p",{className:"font-semibold text-gray-900"},`${flwNames[catName]||catName}`))
                        );
                      });
                    })()
                  ),

                ),

               /* INFO CARD */
             e("div",{className:"bg-gray-50 rounded-2xl p-4 text-sm text-gray-600 flex items-start gap-3"},
                e("i",{
                  "data-lucide":"info",
                  className:"w-5 h-5 text-indigo-500 shrink-0 mt-1"
                }),
                e("div",{className:"space-y-1"},
                  e("h3",{className:"text-gray-900 font-semibold"},
                    "Why Use Partner Services?"
                  ),
                  e("p",{className:"leading-relaxed"},
                    "Earn usage points whenever you use partner services. Reaching ",
                    e("b",null,`${ExpectedTotalCS} points`),
                    " unlocks access to our ",
                    e("b",null,"interest-free Repayable Support Fund (RSF)"),
                    ". As your points grow, you also become eligible for our ",
                    e("b",null,"Annual Community Reward Grant"),
                    "."
                  )
                )
              ),

            )
        );
};



///////////////

const ServicesOnHome = ({categories=[],airtime=[],data=[],setPage,popupOpen,setPopupOpen,setBusy}) => {
  // Always show all 5 partner service icons regardless of DB seed state
  const homeIcons = [
    { key:"airtime",     label:"Airtime",     color:"bg-amber-500",  icon:"smartphone",
      onClick:()=>setPage("services") },
    { key:"data",        label:"Data",        color:"bg-sky-500",    icon:"wifi",
      onClick:()=>setPage("services") },
    { key:"electricity", label:"Electricity", color:"bg-yellow-600", icon:"zap",
      onClick:()=>{ setPopupOpen({open:false,data:{dwat:"viewcatitems",catname:"Electricity",code:"electricity-bill"}}); setPage("pageitems"); } },
    { key:"cable",       label:"Cable TV",    color:"bg-blue-700",   icon:"tv",
      onClick:()=>{ setPopupOpen({open:false,data:{dwat:"viewcatitems",catname:"Cable TV",code:"tv-subscription"}}); setPage("pageitems"); } },
    { key:"education",   label:"Education",   color:"bg-green-700",  icon:"graduation-cap",
      onClick:()=>{ setPopupOpen({open:false,data:{dwat:"viewcatitems",catname:"Education",code:"education"}}); setPage("pageitems"); } },
  ];

  return e("div",{ className: "bg-white rounded-xl shadow-sm border border-gray-100 p-3 mb-3" },
    e("div", { className: "mb-4" },
      e("h3", { className: "text-gray-900 font-bold" }, "Partner Services"),
      e("p", { className: "text-sm text-gray-500" }, "Use partner services to earn usage points.")
    ),
    e("div", { className: "grid grid-cols-3 gap-3" },
      homeIcons.map(ic =>
        e("div",{key:ic.key, className:"flex flex-col items-center gap-2 cursor-pointer", onClick:ic.onClick},
          e("div",{className:`w-14 h-14 ${ic.color} rounded-xl flex items-center justify-center shadow-md`},
            e("i",{"data-lucide":ic.icon, className:"w-7 h-7 text-white"})
          ),
          e("span",{className:"text-xs text-center leading-tight"},
            e("p",{className:"font-semibold text-gray-900"},ic.label)
          )
        )
      ),
      e("div",{key:"more",className:"flex flex-col items-center gap-2 cursor-pointer",onClick:()=>setPage("services")},
        e("div",{className:"w-14 h-14 bg-gray-400 rounded-2xl flex items-center justify-center shadow-md"},
          e("i",{"data-lucide":"more-horizontal",className:"w-7 h-7 text-white"})
        ),
        e("span",{className:"text-xs text-center font-semibold text-gray-900 leading-tight"},"More")
      )
    )
  );
};
/////////////////////
const PageItems = ({services,setServicesData,wallet,Per5Point,setPage,popupOpen,setPopupOpen,setBusy}) => {
  // Capture context on mount — don't re-read from popupOpen so cancel/popup changes don't break the page
  const [billercat]  = useState(popupOpen?.data?.catname || "");
  const [billercode] = useState(popupOpen?.data?.code    || "");
  const [initDwat]   = useState(popupOpen?.data?.dwat    || "viewcatitems");
  const isData       = billercat === "data";
  const [items,setItems]         = useState(services?.[billercode] || null);
  const [groupName,setGroupName] = useState(services?.[billercode]?.[0]?.group_name || "");
  const [durFilter,setDurFilter] = useState("all");

  const filterLabels = [
    {key:"all",     label:"All"},
    {key:"hotdeal", label:"🔥 Hot Deal"},
    {key:"daily",   label:"Daily"},
    {key:"weekly",  label:"Weekly"},
    {key:"monthly", label:"Monthly"},
  ];

  const applyFilter = (allItems, f) => {
    if (!allItems) return [];
    if (f === "hotdeal") return [...allItems].filter(i => i.amount != null).sort((a,b)=>a.amount-b.amount).slice(0,6);
    if (f === "daily")   return allItems.filter(i => parseInt(i.validity_period) === 1);
    if (f === "weekly")  return allItems.filter(i => { const d=parseInt(i.validity_period); return d>=5 && d<=10; });
    if (f === "monthly") return allItems.filter(i => parseInt(i.validity_period) >= 28);
    return allItems;
  };

  // Static billers for cable/electricity/education categories
  const staticBillers = (window.categoryBillers||{})[billercode] || null;
  const pageTitle     = (window.categoryTitles ||{})[billercode] || billercat;

  const loadItems = async () => {
          let url = "", body = {};

          if(initDwat === "viewdataitems"){
              url  = `/client/show.php`;
              body = { type:"data", network:billercode };
          } else if(initDwat === "viewcatitems" && !staticBillers){
              url  = `/client/show.php`;
              body = { type:"bill", category:billercode };
          } else if(initDwat === "viewitems"){
              url  = `/client/show.php`;
              body = { type:"bill", category:billercode };
          }

          if (!url) { if(!staticBillers){ setPage("services"); } return; }

          try {
            setBusy(true);
            const res = await apiFetch(url, body);

            if(res?.status === "failed"){ showAlert(res?.message); setPage("services"); }
            if (res?.status === "success" && res.data) {
              if (res.data?.dataitems){
                  const rawItems = res.data?.dataitems?.[billercode] || [];
                  setGroupName(rawItems?.[0]?.group_name || billercode);
                  setItems(rawItems);
                  saveStorage("lendro.services",{[billercode]:rawItems}, {merge:true});
                  setServicesData(getStorage("lendro.services"));
              } else if (Array.isArray(res.data)) {
                  setItems(res.data);
                  setGroupName(billercode);
              }
            }
          } catch (err) {
            showAlert("Error: "+err, "info");
            setPage("services");
          } finally{
            setBusy(false);
          }
  };

  useEffect(() => {
      loadItems();
  }, []);

  const visibleItems = applyFilter(items, durFilter);

  return e("div", { className: "app-page pb-5" },
            e(inHeader,{pgtitle: staticBillers ? pageTitle : (items ? ShortName(groupName,false) : "Buy Services"),setPage,pg:"services"}),

            e("div", { className: "app-content px-3 pb-5 mt-5 mb-5 space-y-4" },

              // Static biller list (Cable TV / Electricity / Education)
              staticBillers && e("div",{key:"cat-billers",className:"grid grid-cols-3 gap-3 mt-1"},
                staticBillers.map((biller,i)=>
                  e("div",{key:biller.code||i,className:"flex flex-col items-center gap-2 cursor-pointer p-3 bg-white rounded-xl border border-gray-100 shadow-sm",
                    onClick:()=>{
                      const dwatMap={"tv-subscription":"buycable","electricity-bill":"buyelectricity","education":"buyeducation"};
                      const dwat=dwatMap[billercode]||"buybill";
                      setPopupOpen({open:true,data:{dwat,provider:biller.code,providerName:biller.name,catname:billercat,catcode:billercode}});
                    }},
                    e("div",{className:`w-12 h-12 ${biller.img?"bg-white border border-gray-100":biller.color} rounded-xl flex items-center justify-center shadow-md overflow-hidden`},
                      biller.img
                        ? e("img",{src:biller.img,alt:biller.name,className:"w-full h-full object-contain p-1 rounded-xl"})
                        : e("i",{"data-lucide":biller.icon,className:"w-6 h-6 text-white"})
                    ),
                    e("p",{className:"text-xs font-bold text-gray-900 text-center"},biller.name),
                    biller.label&&e("p",{className:"text-xs text-gray-500 text-center"},biller.label)
                  )
                )
              ),

              // Duration filter tabs — shown only for data bundles
              isData && e("div", { className: "flex gap-2 flex-wrap pt-1" },
                filterLabels.map(fl =>
                  e("button", {
                    key: fl.key,
                    onClick: () => setDurFilter(fl.key),
                    className: `px-3 py-1 rounded-full text-xs font-semibold border transition-colors ${durFilter === fl.key
                      ? "bg-indigo-600 text-white border-indigo-600"
                      : "bg-white text-gray-600 border-gray-200 hover:border-indigo-400"}`
                  }, fl.label)
                )
              ),

              (!staticBillers) && (items) && e("h3",{className:"text-base font-semibold text-gray-700 mt-1"},
                durFilter === "all"
                  ? `Explore our ${UCaseNetworkOnly(groupName)} below.`
                  : `${filterLabels.find(f=>f.key===durFilter)?.label} plans`
              ),

              (!staticBillers) && e("div", { key: "preamounts", className: "flex flex-wrap items-center gap-3 mx-auto w-full mb-5" },
                (visibleItems.length > 0) ? visibleItems.map((item,i)=>{
                      return e("span", { key:i,
                        className: "flex-[0_0_calc(33.333%-1rem)] px-3 py-2 bg-indigo-500/20 rounded-lg text-lg text-center min-h-[130px] cursor-pointer hover:bg-indigo-500/30 transition-colors",
                        // Fix: pass dwat:"buydata" so the popup billerForm renders correctly
                        onClick:()=>setPopupOpen({open:true, data:{...popupOpen?.data, dwat:"buydata", item}})
                      },
                      e("p",{className:"text-md font-semibold"},`${ShortName(item.biller_name)}`),
                      e("p",{className:"text-md font-semibold"},`${formatCurrency(item.amount,false)}`),
                      e("p",{className:"text-xs font-semibold"},`${item.validity_period} ${(item.validity_period < 2)?"Day":"Days"} validity`),
                      e("p",{className:"text-xs"},`${PlanType(item.validity_period)}`),
                    )
                }) : (!staticBillers) ? e("div",{className:"w-full text-center py-8 text-gray-400 text-sm"},
                        (isData && durFilter !== "all")
                          ? `No ${filterLabels.find(f=>f.key===durFilter)?.label} plans found.`
                          : "Loading plans…"
                      ) : null
              )
            )
        );
};
