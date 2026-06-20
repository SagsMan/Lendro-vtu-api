function Home({user,wallet, categories, airtime, data, greeting,maxCUPoint,transactions,leaderboard, Per5Point,setPage,popupOpen,setPopupOpen,setBusy}){
  const scores = wallet?.scores;
  myPlan = wallet?.plan || null;
  //console.log("Plan: ",myPlan);

  return e("div", { className: "app-page" },
              e(Header,{wallet,Per5Point,setPage}), //Header Component
              
              e("div", { className: "app-content px-3 pb-5 mt-6 space-y-6" },
                  e(BoostNotification,{setPage}), // Boost Your Score Notification
                  e(ServicesOnHome,{categories,airtime,data,setPage,popupOpen,setPopupOpen,setBusy}), 
                  e(UpgradeNotifier,{setPage}),
                  e(AboutInfoCard,{setPage}), // About Info Card
              )
  );
}

//////Boost yellow Notification Component.......gradient-to-r from-[#FFC107] to-[#FFD54F]  shadow-amber-200/50 bg-gray-100 rounded-xl p-4 shadow-lg shadow-gray-300/50 mb-6
const BoostNotification = ({setPage}) => {
  //myPlan = parseInt(wallet?.plan || 0);
  return e("div", {className: "" },
                    (!myPlan || myPlan == "0") && e("div", {className: "rounded-xl bg-yellow-100 hover:bg-yellow-200 shadow-lg shadow-gray-200/50 transition-colors cursor-pointer mb-6 p-4" },
                      e("p", { className: "text-gray-900 text-sm text-center" },
                      e("span", { className: "font-bold block text-xl" },`Get Started`),
                        "Buy a plan, enjoy data, earn rewards through the oShare program, unlock grants, and access up to ₦650,000+ in support funding.", 
                        e("a", { className: "font-bold w-1/2 mx-auto mt-3 py-2 rounded-lg text-white bg-indigo-600 block text-sm",onClick:()=>setPage("join") },`Unlock Plan`),
                      )
                    )
                );
};
/////////////////
const UpgradeNotifier = ({setPage}) => {
  const thisPlan = Plans[myPlan] || [];
  const currentPlanName = thisPlan[0] || "";
  let nextPlan = Plans[parseInt(myPlan) + 1] || null;
  let nextPlanName = nextPlan?.[0] || null;

  return (myPlan > 0 && myPlan < 6 && nextPlanName) && e("div", {className: "" },
                    e("div", {className: "rounded-xl bg-lime-50 hover:bg-lime-100 shadow-lg shadow-gray-200/50 transition-colors cursor-pointer mb-6 px-4 py-3"},
                        e("div", {className: ""}),
                        e("div", { className: "flex items-center justify-between" },
                          e("div", null, e("p", { className: "text-xs text-gray-500" }, "Your Current Plan"), e("h3", { className: "text-lg font-bold text-gray-900" }, currentPlanName)
                          ), e("span", { className: "px-2 py-2 rounded text-sm font-semibold bg-white text-gray-500 cursor-pointer",onClick: () => setPage("join") }, "Upgrade Plan")),
                        /*e("p", {className: "text-sm text-gray-600 text-left leading-relaxed"},
                          `Move to ${nextPlanName} and unlock higher oShare rewards, more points, and increased funding access. `,e("span", {onClick: () => setPage("join"),className: "font-semibold underline text-indigo-500 cursor-pointer" },"Upgrade Plan")),*/
                      ),
                );
};
///////////////
const AboutInfoCard = ({setPage}) => { 
  return e( "div", { className: "bg-indigo-700 rounded-2xl p-5 shadow shadow-indigo-300/50 text-white", }, 
        e( "div", { className: "flex items-start gap-3 mb-3" }, 
          e( "div", { className: "w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center flex-shrink-0", }, 
            e("i", { "data-lucide": "info", className: "w-5 h-5 text-[#4F46E5]", }) 
          ), 
          e( "h3", { className: "font-bold text-xl" }, "Why Use Lendro") 
        ), 
        e( "p", { className: "text-indigo-100 text-sm leading-relaxed mb-2" }, "Lendro is a community support app built for the empowerment of community members to;"),
        e("ul", { className: "list-disc list-outside ps-4 text-indigo-100 text-sm leading-relaxed" },
          e("li", { className: "mb-0" }, "Buy Data at Cheaper price"),
          e("li", { className: "mb-0" }, "Get Repayable Support Funds (RSF)"),
          e("li", { className: "mb-0" }, "Earn Rewards through the ",e("span",{className:"underline hover:no-underline text-cyan-300 cursor-pointer",onClick:(e)=>setPage("wcfund")},`oShare program`)),
          e("li", { className: "mb-0" }, "Access Grants"),
        ),
        
        e( "p", { className: "text-indigo-100 text-sm leading-relaxed", }, "Our programs are supported by partner organizations that contribute to community development when members patronize their services.",
          e( "span", { className: "text-cyan-300 font-semibold cursor-pointer hover:underline ml-1", onClick:()=>setPage("about")}, "Learn more" ) 
         )
      );
};
///////////// Todo Boost Your Score Component ///////////// ${ (myPlan && myPlan > 0) ? "bg-indigo-600" : "bg-gray-600"}
const BoostTask = ({setPage}) => {
   return e("div",  { className: "",}, //bg-white rounded-xl p-3 shadow-lg shadow-gray-200/50
      e("h3", { className: "font-bold text-gray-900 mb-3" }, "Get Started"),
      e("div",{ className: "grid grid-cols-2 gap-2" },
        
        (!myPlan || myPlan == "0") && e( "div", { className: "flex flex-col items-center gap-3 p-2 rounded-xl bg-white shadow-lg shadow-gray-200/50 hover:bg-gray-100 transition-colors cursor-pointer", onClick:()=>setPage("join") }, e( "div", { className: `w-12 h-12 rounded-full bg-gray-600/10 flex items-center justify-center flex-shrink-0`, }, e("i", { "data-lucide": "file-check", className: `w-6 h-6 text-gray-800`, }) ), e( "div", { className: "flex-1 text-center" }, e( "p", { className: "font-semibold text-sm leading-tight mb-1 text-gray-900", }, "Verify KYC & participate to unlock funds & points" ), 
        e( "span", { className: "text-indigo-600 font-bold text-sm", }, "(30-80 pts)" ) 
        ) ),

        e( "div", { className: "flex flex-col items-center gap-3 p-2 rounded-xl bg-white shadow-lg shadow-gray-200/50 hover:bg-gray-100 transition-colors cursor-pointer", onClick:()=>setPage("services") }, e( "div", { className: "w-12 h-12 rounded-full bg-gray-600/10 flex items-center justify-center flex-shrink-0", }, e("i", { "data-lucide": "shopping-cart", className: "w-6 h-6 text-gray-800", }) ), e( "div", { className: "flex-1 text-center" }, e( "p", { className: "font-semibold text-sm leading-tight mb-1 text-gray-900", }, "Use partner services to earn more points." ), 
        e( "span", { className: "text-indigo-600 font-bold text-sm", }, `(${UPointPerPurchase} pts)` ) 
        ) ),

        (myPlan && myPlan > 0 && myPlan <= 6) && e( "div", { className: "flex flex-col items-center gap-3 p-2 rounded-xl bg-white shadow-lg shadow-gray-200/50 hover:bg-gray-100 transition-colors cursor-pointer", onClick:()=>setPage(`${(myPlan < 6 ? "join" : "benefits")}`) }, e( "div", { className: `w-12 h-12 rounded-full bg-gray-600/10 flex items-center justify-center flex-shrink-0`, }, e("i", { "data-lucide": "file-check", className: `w-6 h-6 text-gray-800`, }) ), e( "div", { className: "flex-1 text-center" }, e( "p", { className: "font-semibold text-sm leading-tight mb-1 text-gray-900", }, `${(myPlan < 6) ? "Upgrade your account for more benefits & points" : "You've reached the top plan - enjoy all rewards!"}` ), 
        e( "span", { className: "text-indigo-600 font-bold text-sm", }, `${(myPlan < 6) ? "(Upgrade)":"(View Benefits)"}` ) 
        ) ),

        //e( "div", { className: "flex flex-col items-center gap-3 p-2 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer", }, e( "div", { className: "w-12 h-12 rounded-full bg-gray-600/10 flex items-center justify-center flex-shrink-0", }, e("i", { "data-lucide": "users", className: "w-6 h-6 text-gray-800", }) ), e( "div", { className: "flex-1 text-center" }, e( "p", { className: "font-semibold text-sm leading-tight mb-1 text-gray-900", }, "Get Community Trust Score" ), e( "span", { className: "text-indigo-600 font-bold text-sm", }, `(${CTPointPerMember}pt)` ) ) )
      )
    );
};

///////// Leaderboard Component /////////////////////////////// 🏆🥈🥉👨
const Leaderboard = ({leaderboard}) =>
  /*e("div", { className: "bg-white rounded-2xl p-5 shadow shadow-gray-300/50" },
    e("div", { className: "mb-3" },
      e("div", { className: "flex items-center gap-2 mb-1" },
        e("h3", { className: "font-bold text-gray-900" }, "Top 20 Grant Leaderboard"),     
      ),
    ),
    e("div", { className: "space-y-2" },
      (leaderboard && leaderboard?.length > 0) ? leaderboard.map(user => leaderboardRow(user?.rank, user?.icon, user?.name, user?.score, user?.highlight) ): e("div", { className: "text-gray-500 bg-yellow-500 text-white p-3 rounded-xl text-md text-center" }, "No user qualify for the grant yet - Buy services and be the first to qualify for grant.")
    )
  );*/

  e("div",{className:"bg-white rounded-2xl p-5 shadow-lg shadow-gray-200/60 space-y-2"},
      e("div",{className:"flex items-center justify-between"},
        e("div",{className:"flex items-center gap-2"},
          e("i",{ "data-lucide":"trophy", className:"w-5 h-5 text-indigo-600"}),
          e("h3",{className:"font-bold text-gray-900"},"Top 20 Grant Leaderboard")
        )
      ),
      e("p",{className:"text-sm text-gray-500"},"Members with the highest usage points qualify for the Annual Reward Grant."),
      e("div",{className:"space-y-2"},
        (leaderboard && leaderboard?.length > 0) ? leaderboard.map(user => leaderboardRow( user?.rank, user?.icon, user?.name, user?.score, user?.highlight) ) : 
        e("div",{className:"bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center space-y-2"},              
            e("i",{ "data-lucide":"sparkles", className:"w-6 h-6 text-yellow-500 mx-auto"}),
            e("p",{className:"text-sm font-semibold text-gray-800"},"No members qualify yet"),
            e("p",{className:"text-xs text-gray-500"},"Use partner services to earn points and be the first to appear on the leaderboard.")
        )
      )
    );

// Reusable row
function leaderboardRow(rank, icon, name, score, highlight = false) {
  return e("div", {key:rank, className: "flex items-center gap-3 p-3 rounded-xl " + (highlight ? "bg-gradient-to-r from-[#4F46E5]/10 to-[#6366F1]/10 border-2 border-[#4F46E5]" : "bg-gray-100") },
    e("div", { className: "w-5 h-5 flex items-center justify-center font-bold text-gray-600 text-sm" }, rank ),
    e("span", { className: "flex items-center justify-center text-[#4F46E5] text-lg"},icon), //"data-lucide": "", 
    e("div", { className: "flex-1" }, e("div", { className: "font-semibold text-sm text-gray-900" }, name) ),
    e("div", { className: "font-bold text-sm text-[#4F46E5]" }, `${score}pts`)
  );
};