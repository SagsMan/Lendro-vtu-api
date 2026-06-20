const Wallet = ({wallet,setPage, transactions=[],popupOpen,setPopupOpen,setBusy}) => {

    const balance     = wallet?.balance || 0;
    const bucbalance  = Number(wallet?.bucbalance ?? 0).toFixed(4);
    const rawScores   = wallet?.scores || {};
    let {totalCreditScore} = GetTotalScores(rawScores);

    // Read stored KYC status and virtual account (set after successful KYC)
    const kycStatus      = getStorage("lendro.kyc_status") || "";
    const virtualAccount = getStorage("lendro.virtual_account") || null;
    const isVerified     = kycStatus === "verified";

  const BalanceCard = ({balance, bucbalance}) =>
  e("div",{className:"rounded-2xl p-5 shadow-lg bg-gradient-to-br from-indigo-600 via-indigo-600 to-indigo-500 text-white"},

    // Header
    e("div",{className:"flex items-center justify-between mb-3"},
      e("p",{className:"text-sm opacity-80"},"Total Wallet Balance"),
      e("i",{ "data-lucide":"wallet", className:"w-7 h-7 opacity-90"})
    ),

    // Main Balance
    e("h2",{className:"text-3xl font-bold tracking-wide mb-3"},
      formatCurrency(balance.toLocaleString())
    ),

    // Divider
    e("div",{className:"border-t border-white/20 my-3"}),
    // Bonus Section
    e("div",{className:"flex items-center justify-between"},
      e("div",{className:"flex items-center gap-1"},
        e("i",{ "data-lucide":"coins", className:"w-4 h-4"}),
        e("span",{className:"text-sm opacity-90"},"Bucoin Bonus")
      ),
      e("span",{className:"font-semibold text-yellow-300"},
        `${bucbalance} $BUC`
      )
    ),
    // Total points earned Section
    e("div",{className:"flex items-center justify-between"},
      e("div",{className:"flex items-center gap-1"},
        e("i",{ "data-lucide":"user-star", className:"w-4 h-4"}),
        e("span",{className:"text-sm opacity-90"},"Total Points Earned")
      ),
      e("span",{className:"font-semibold text-yellow-300"}, `${totalCreditScore} pts`)
    ),
    
  );


    const TxRow = ({ icon,iconBg,iconColor,title, time, amount, amountColor })=>{
        return e("div", {className: "flex items-center gap-3 p-3 rounded-2xl bg-white" },
            e("div", { className: `w-10 h-10 rounded-full flex items-center justify-center ${iconBg}`},
            e("i", {"data-lucide": icon, className: `w-5 h-5 ${iconColor}` })
            ),

            e("div", { className: "flex-1" },
            e("div", { className: "font-semibold text-sm text-gray-900" }, title),
            e("div", { className: "text-xs text-gray-500" }, time)
            ),

            e("div", { className: `font-bold ${amountColor}` }, amount)
        );
    };

  return e("div",{className:"app-page pb-5"},
    e(inHeader,{pgtitle:"Wallet",setPage,pg:"home",icons:[2]}),

    e("div",{className:"app-content px-3 mt-5 space-y-4"},

      BalanceCard({
        balance: balance,
        bucbalance: bucbalance
      }),

      // Deposit + Withdraw row
      e("div",{className:"mb-5 flex gap-3"},
        e("button",{
          className:"flex-1 bg-yellow-500 hover:bg-yellow-400 text-gray-900 py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition",
          onClick: () => setPopupOpen({open:true, data:{dwat:"deposit"} })
        },
          e("i",{ "data-lucide":"arrow-down-circle", className:"w-5 h-5"}),
          "Deposit"
        ),
        e("button",{
          className:"flex-1 bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition",
          onClick: () => setPopupOpen({open:true, data:{dwat:"withdraw"} })
        },
          e("i",{ "data-lucide":"arrow-up-circle", className:"w-5 h-5"}),
          "Withdraw"
        )
      ),

      // ── Virtual Account / KYC section ─────────────────────────────────────
      isVerified && virtualAccount?.account_number
        // Already verified + has virtual account → show account card
        ? e("div",{className:"bg-indigo-50 border border-indigo-200 rounded-2xl p-4 space-y-3"},
            e("div",{className:"flex items-center gap-2 mb-1"},
              e("i",{"data-lucide":"building-2", className:"w-5 h-5 text-indigo-600"}),
              e("h4",{className:"font-bold text-gray-900 text-sm"},"Your Virtual Account")
            ),
            e("p",{className:"text-xs text-gray-500"},"Transfer to this account to fund your wallet instantly."),
            e("div",{className:"flex justify-between items-center"},
              e("span",{className:"text-sm text-gray-600"},"Bank"),
              e("span",{className:"font-semibold text-gray-900"}, virtualAccount.bank_name || "GTBank")
            ),
            e("div",{className:"flex justify-between items-center"},
              e("span",{className:"text-sm text-gray-600"},"Account Number"),
              e("span",{className:"font-bold text-xl text-indigo-700 tracking-widest"}, virtualAccount.account_number)
            ),
            virtualAccount.account_name && e("div",{className:"flex justify-between items-center"},
              e("span",{className:"text-sm text-gray-600"},"Account Name"),
              e("span",{className:"font-semibold text-gray-900"}, virtualAccount.account_name)
            )
          )
        // Not yet verified → show "Generate Virtual Account" CTA
        : e("div",{className:"bg-white border border-dashed border-indigo-300 rounded-2xl p-4"},
            e("div",{className:"flex items-start gap-3"},
              e("div",{className:"w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0"},
                e("i",{"data-lucide":"shield-check", className:"w-5 h-5 text-indigo-600"})
              ),
              e("div",{className:"flex-1"},
                e("h4",{className:"font-bold text-gray-900 text-sm mb-1"},"Get Your Virtual Bank Account"),
                e("p",{className:"text-xs text-gray-500 mb-3"},
                  "Verify your identity with your NIN or BVN to receive a personal account number. " +
                  "You can use it to fund your wallet anytime via bank transfer."
                ),
                e("button",{
                  className:"bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-sm font-semibold transition active:scale-95 flex items-center gap-2",
                  onClick: () => setPopupOpen({open:true, data:{dwat:"kyc"}})
                },
                  e("i",{"data-lucide":"badge-check", className:"w-4 h-4"}),
                  "Generate Wallet"
                )
              )
            )
          ),

      // ── Transaction history ────────────────────────────────────────────────
      e("div",{className:"pt-5"},
        e("h3",{className:"font-bold text-gray-900 mb-2 flex items-center gap-2"},
          e("i",{ "data-lucide":"list", className:"w-5 h-5 text-indigo-600"}),
          "Transaction History"
        ),
        e("div", { className: "space-y-2" },
        transactions.length === 0
          ? e("p",{className:"text-gray-500 p-3 rounded-2xl bg-white text-sm"},"No transactions yet.")
          : transactions.map((tx, i) =>
                e(TxRow, {
                  key: tx.id || `${tx.type}-${tx.time}-${i}`,
                  icon: GetTxSigns("icon", tx.type,tx?.status),
                  iconBg: GetTxSigns("bg", tx.type,tx?.status),
                  iconColor: GetTxSigns("color", tx.type,tx?.status),
                  title: tx.description,
                  time: tx.time,
                  amount: (GetTxSigns("sign", tx.type,tx?.status)) + formatCurrency(tx.amount),
                  amountColor: GetTxSigns("num", tx.type,tx?.status)
                })
            )
        )
      )
    )
  );
};
