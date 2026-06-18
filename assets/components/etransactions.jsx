const arrowDown = "arrow-down-right";
const arrowUp = "arrow-up-right";

const RecentTransactions = ({transactions}) => {
 if (!transactions || transactions.length === 0) return null;

  return e("div", { className: "" }, //bg-white rounded-xl p-5 shadow-lg shadow-gray-200/50
    e("h3", { className: "font-bold text-gray-900 mb-3" }, "Recent Transactions"),
    e("div", { className: "space-y-2" },
      // Transaction row (Deposit)
      transactions.map((tx, i) =>
        e(transactionRow,{
          key: tx.id || `${tx.type}-${tx.time}-${i}`, 
          icon: GetTxSigns("icon",tx.type,tx?.status),
          iconBg: GetTxSigns("bg",tx.type,tx?.status),
          iconColor: GetTxSigns("color",tx.type,tx?.status),
          title: tx.description,
          time: tx.time,
          amount: (GetTxSigns("sign",tx.type,tx?.status)) + formatCurrency(tx.amount),
          amountColor: GetTxSigns("num",tx.type,tx?.status)
        })
      )

    )
  );
};

// Reusable transaction row
function transactionRow({ icon,iconBg,iconColor,title, time, amount, amountColor }) {
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