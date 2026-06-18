const WCFund = ({ wallet, setPage, shares = {} }) => {

const thisPlan = Plans[wallet?.plan] || [];
const planName = thisPlan[0] || null;
const planShare = thisPlan[1] || 0.00;

const osPool = shares?.osPool || 0; //last oshare Pool in Naira
const osPercent = shares?.osPercent || osharePercent;
const myallShares = wallet?.ostotalearn || 0;
const myBalanceShare = wallet?.osbalance || 0;
const myWithdrawnShare = (parseFloat(myallShares) - parseFloat(myBalanceShare)) || 0;
const mylastShare = wallet?.oslastearn || 0;

const hasPlan = !!planName;

return e("div", { className: "app-page" },

  e(inHeader, { pgtitle: "oShare Rewards", setPage, pg: "home" }),

  e("div", { className: "px-3 py-5 space-y-4" },

    // oShare Summary Card 
    hasPlan && e(oShareCards,{osPool,osPercent,mylastShare,myallShares,"myplanname":planName,"myplanshare":planShare}),

    // USER REWARDS 
    hasPlan && e("div", {className: "bg-white rounded-2xl p-5 shadow space-y-3"},      
      e("div", null,
        e("div", { className: "flex items-center justify-between" },
          e("div", null, 
            e("p",{className: "text-sm text-gray-500"},"Lifetime Rewards"),
            e("h3", { className: "text-2xl font-bold text-indigo-600" },`${formatCurrency(myallShares,true)}`)
          ),
          //e("i", { "data-lucide": "wallet", className: "w-6 h-6 text-indigo-500" })
        ),        
      ),
      e("div", { className: "grid grid-cols-2 gap-2 text-sm" },
        e("div", { className: "border p-2 rounded-lg" },
          e("p", { className: "text-gray-500" }, "Sent to Wallet"),
          e("p", { className: "font-semibold text-gray-900" }, `${formatCurrency(myWithdrawnShare,true)}`)
        ),
        e("div", { className: "border p-2 rounded-lg" },
          e("p", { className: "text-gray-500" }, "Balance"),
          e("p", { className: "font-semibold text-green-600" }, `${formatCurrency(myBalanceShare,true)}`)
        )
      )
    ),

        // NOT A MEMBER 
    !hasPlan && e("div", {className: "bg-white rounded-2xl shadow p-6 text-center space-y-4"},
      e("div", {className: "w-14 h-14 mx-auto rounded-full bg-indigo-100 flex items-center justify-center"
      }, e("i", { "data-lucide": "users", className: "w-7 h-7 text-indigo-600" })),
      e("h3", { className: "font-bold text-lg text-gray-900" }, "Unlock oShare Rewards"),
      e("p", { className: "text-sm text-gray-500" }, "Join a premium plan to access rewards from partner activities, grants, and support opportunities." ),
      e("button", { onClick: () => setPage("join"), className: "w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-semibold transition"}, "Choose a Plan") 
    ),

    // UPGRADE CTA 
    hasPlan && e("div", { className: "flex justify-between gap-3"},
      e("button", { onClick: () => setPage("join"), className: "w-1/2 bg-indigo-600 hover:bg-indigo-500 text-white py-2 rounded-xl font-semibold transition"}, "Upgrade Plan"),
      e("button", { onClick: () => setPage("wallet"), className: "w-1/2 bg-yellow-400 hover:bg-yellow-500 text-indigo-900 py-2 rounded-xl font-semibold transition"}, "Transfer Fund")
    ),

    // EXPLANATION 
    e("div", { className: "bg-gray-50 rounded-2xl p-4 text-xs text-gray-600 flex gap-2" },
      e("i", { "data-lucide": "info", className: "w-4 h-4 mt-[2px] text-gray-500" }),
      e("span", null,
        "oShare distributes 25% of partner contributions among premium members based on their plan activity and share weight."
      )
    ),

  )
);
};


const oShareCards = ({osPool,osPercent,mylastShare,myallShares,myplanname,myplanshare})=>{

  return e("div", {className: "bg-gradient-to-br from-indigo-600 to-indigo-500 text-gray-100 rounded-2xl p-4 shadow-lg"},
      e("div", { className: "flex items-center justify-between mb-3" },
        e("h3", { className: "font-bold" }, "oShare Summary"),
        e("i", { "data-lucide": "bar-chart-3", className: "w-5 h-5 text-indigo-300" })
      ),

      e("div", { className: "grid grid-cols-2 gap-2 text-sm" },
          e("div", {className:"bg-indigo-800/25 p-2 rounded-lg"},
            e("p", { className: "text-cyan-300" }, "Partner Share"),
            e("p", { className: "font-semibold" }, `${osPercent}%`)
          ),

          e("div", {className:"bg-indigo-800/25 p-2 rounded-lg"},
            e("p", { className: "text-cyan-300" }, "Plan"),
            e("p", { className: "font-semibold" }, `${myplanname || "N/A"}`)
          ),

          e("div", {className:"bg-indigo-800/25 p-2 rounded-lg"},
            e("p", { className: "text-cyan-300" }, "Share Weight"),
            e("p", { className: "font-semibold" }, `${myplanshare}`)
          ),

          e("div", {className:"bg-indigo-800/25 p-2 rounded-lg"},
            e("p", { className: "text-cyan-300" }, "Last Reward"),
            e("p", { className: "font-semibold" }, `${formatCurrency(mylastShare,true)}`)
          )
        ),
    );
}