const Header = ({wallet,Per5Point,setPage}) => {
  const balance = wallet?.balance || 0.00;
  const oShareBal = wallet?.osbalance || 0.00;
  //const bucbalance = Number(wallet?.bucbalance ?? 0).toFixed(3);
  const outstanding = wallet?.outstanding || 0.00;
  const loanlimit = wallet?.loanlimit || 0.00;
  const scores = wallet?.scores || {};
  const totalscore = wallet?.totalscore || 0.00;

  let {totalCreditScore,UP2Percent,CTP2Percent} = GetTotalScores(scores,Per5Point);

  return e("div", { className: "bg-indigo-800 text-white px-3 pt-4 pb-6 rounded-b-[22px]" },
                    e("div", { className: "flex justify-between items-center mb-5" },
                        e("h1", { className: "text-3xl font-black tracking-tight" }, "LEN",e("span",{className:"text-yellow-400"},"DRO")),
                        e("div",{ className: "flex items-end justify-end" }, 
                            e("a",{className:"w-9 h-9 mx-2 rounded-full bg-indigo-200/10 backdrop-blur-sm flex items-center justify-center",onClick:()=>setPage("wallet")},e("i", {"data-lucide": "wallet", className: "w-5 h-5 text-white"})),
                            e("a",{className:"w-9 h-9 rounded-full bg-indigo-200/10 backdrop-blur-sm flex items-center justify-center",onClick:()=>setPage("notifications")},e("i", {"data-lucide": "bell", className: "w-5 h-5 text-white"}))
                        )
                    ),
                    
                    /* Loan Card */
                    e("div", { className: "bg-indigo-200/10 rounded-2xl p-5" },
                        e("p", { className: "text-indigo-200 text-sm" }, "Support Funding Limit"),
                        e("h2", { className: "text-5xl font-bold" }, formatCurrency(loanlimit,true)),
                        e("div", { className: "flex justify-between items-start mt-3" },
                            e("div", { className: "w-1/2 text-left border-t border-white/20 pt-3 me-1" },
                                e("p", { className: "text-sm text-indigo-200" }, "Outstanding"),
                                e("p", { className: "text-xl font-bold" }, formatCurrency(outstanding,true))
                            ),
                            e("div", {className:"w-1/2 text-left border-t border-white/20 pt-3 ms-1"},
                                e("p", { className: "text-sm text-indigo-200" }, `Total Points Earned`),
                                e("p", { className: "text-xl font-bold" }, `${totalscore || totalCreditScore}`,
                                  //e("span", { className: "text-xs font-normal text-indigo-100" }, ` / ${ExpectedTotalCS} Points`)
                                )
                            )
                        ),
                        e("div", { className: "flex justify-between items-start mt-3" },
                            e("div", { className: "w-1/2 text-left border-t border-white/20 pt-3 me-1" },
                                e("p", { className: "text-sm text-indigo-200" }, "Wallet Balance"),
                                e("p", { className: "text-xl font-bold" }, formatCurrency(balance),
                                  //e("span",{className:"text-xs text-yellow-300 font-semibold ps-1"},`(${bucbalance} BUC)`)
                                )
                            ),
                            e("div", {className:"w-1/2 text-left border-t border-white/20 pt-3 ms-1"},
                                e("p", { className: "text-sm text-indigo-200" }, "oShare Balance"),
                                e("p", { className: "text-xl font-bold" }, formatCurrency(oShareBal,true))
                            )
                        )
                    ),
                    //Buttons
                    e("div", { className: "bg-indigo-200/10 rounded-2xl p-5 mt-4" },
                        e( "div", { className: "grid grid-cols-2 gap-3" }, 
                            e( "button", { className: "bg-indigo-600 text-white py-3 rounded-2xl font-semibold text-sm hover:bg-indigo-700 transition shadow-md",onClick:()=>setPage("wallet") }, "Deposit Fund" ), 
                            e( "button", { className: "bg-yellow-400 text-indigo-900 py-3 rounded-2xl font-semibold text-sm hover:bg-yellow-300 transition shadow-md",onClick:()=>setPage("loan") }, "Request Funding" ) 
                        )
                    )
            );
};

//Inner page header with logo/wallet/notify......
const innerHeader = ({setPage}) => {
  return e("div",{className: "sticky top-0 z-40 bg-white/80 backdrop-blur-md px-5 py-4 shadow-[0_2px_12px_rgba(0,0,0,0.06)]" },
    e("div", { className: "flex items-center justify-between" },
      e("h1", { className: "text-3xl font-extrabold tracking-tight text-indigo-600" },"LEN",
         e("span", { className: "text-yellow-400" }, "DRO") ),
      e("div", { className: "flex items-center gap-2" },
        e("button",{className: "w-10 h-10 rounded-full bg-indigo-600/10 hover:bg-indigo-600/20 transition flex items-center justify-center",onClick:()=>setPage("wallet") },
          e("i", {"data-lucide": "wallet", className: "w-5 h-5 text-indigo-600" })
        ),
        e("button",{className: "w-10 h-10 rounded-full bg-indigo-600/10 hover:bg-indigo-600/20 transition flex items-center justify-center",onClick:()=>setPage("notifications") },
          e("i", {"data-lucide": "bell", className: "w-5 h-5 text-indigo-600" })
        )
      )
    )
  );
};

//Inner page header with back button and/or history......
const inHeader = ({pgtitle,setPage,pg="home",icons=[1,2]}) => {
  return e("div",{className: "sticky top-0 z-40 bg-gray-100/80 backdrop-blur-md px-4 py-4" }, //shadow-[0_2px_12px_rgba(0,0,0,0.06)]
    e("div", { className: "flex items-center justify-between" },
      e("h1", { className: "text-xl flex items-center font-bold tracking-tight text-indigo-600",onClick:()=>setPage(pg || "home") },
         e("i", {"data-lucide": "arrow-left", className: "w-6 h-6 pe-1 text-indigo-600 hover:text-indigo-900" }),
         e("span", { className: "text-indigo-600" }, `${pgtitle}`) 
      ),
      (icons) && e("div", { className: "flex items-center gap-3" },
        (icons.includes(1)) && e("button",{className: "w-6 h-6",onClick:()=>setPage("wallet") },
          e("i", {"data-lucide": "wallet", className: "w-5 h-5 text-indigo-600" })
        ),
        (icons.includes(2)) && e("button",{className: "w-6 h-6",onClick:()=>setPage("notifications") },
          e("i", {"data-lucide": "bell", className: "w-5 h-5 text-indigo-600" })
        )
      )
    )
  );
};