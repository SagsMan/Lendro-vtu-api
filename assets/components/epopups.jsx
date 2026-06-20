//////// After Deposit or Withdrawal Confirm...
const VerifyFund = async ({refno,total,setWalletData,setMaxCUPoint,setTransactions})=>{
      const r = await apiFetch("/accounts/deposit.php", {category:"deposited",status:"processed",refno:refno,total:total});
      try{
              if (r.status === "success" && r.data) {
                  if(r.data?.wallet){
                      saveStorage("lendro.wallet",r.data?.wallet,{merge:true});
                      setWalletData(r.data?.wallet);
                      setMaxCUPoint(r.data?.wallet.maxusage);
                  }
                  if(r.data?.transactions){
                    setTransactions(prev => [r.data?.transactions, ...prev]);
                  }
                  alert(`${formatCurrency(r.data?.amount)} deposit was successful.`);
              }else{
                alert(`Deposit of ${formatCurrency(r.data?.amount || total)} failed. Contact support if your bank was debited.`);
              }
      }catch(e){
        alert("Process interrupted, contact us for assistance!");
      }
}

//////////////////// Buy......
const Buy = async ({category,data,setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions})=>{
    let url = "";
    let body = {};

    // Generate a simple idempotency key to prevent duplicate submissions
    const idempotencyKey = "ik_" + Date.now() + "_" + Math.random().toString(36).slice(2,9);

    console.log(category, data);

    if(category === "deposit"){
      if(data?.amount < 100) { alert(`Minimum allowed limit is ${formatCurrency(100)}`); return; }
      if(data?.total < 100)  { alert(`Minimum allowed limit is ${formatCurrency(100)}`); return; }
      url  = "/accounts/deposit.php";
      body = {category,status:"pending",amount:data?.amount,fee:data?.fee,total:data?.total};

    }else if(category === "airtime"){
      if(!data?.myphone || data?.myphone.length <= 1) { alert("Recipient phone number is required!"); return; }
      else if(data?.amount < 100) { alert(`Minimum allowed limit is ${formatCurrency(100)}`); return; }
      else if(data?.balance < 100) { alert(`Insufficient balance (${formatCurrency(data?.balance,true)}) in your wallet.`); return; }
      // service_id comes from the airtime network row (added to getAllServices response)
      url  = "/client/order.php";
      body = {service_id:data?.service_id, phone:data?.myphone, amount:data?.amount, idempotency_key:idempotencyKey};

    }else if(category === "data"){
      if(!data?.myphone || data?.myphone.length <= 1) { alert("Recipient phone number is required!"); return; }
      else if(data?.balance < 100) { alert(`Insufficient balance (${formatCurrency(data?.balance,true)}) in your wallet.`); return; }
      // service_id is the DB id of the data plan item (included in show.php response)
      url  = "/client/order.php";
      body = {service_id:data?.service_id, phone:data?.myphone, idempotency_key:idempotencyKey};

    }else if(!category){ return; }

    try{
      setBusy(true);
      const res = await apiFetch(url, body);

      if (res?.status === "success" || res?.status === "processing") {
        if(res.data?.wallet){
            saveStorage("lendro.wallet",res.data?.wallet,{merge:true});
            setWalletData(res.data?.wallet);
            setMaxCUPoint(res.data?.wallet.maxusage);
        }
        if(category === "airtime"){
          alert(`${formatCurrency(data?.amount)} airtime request submitted. Ref: ${res?.reference || ""}`);

        }else if(category === "data"){
          alert(`${formatCurrency(data?.amount)} data request submitted. Ref: ${res?.reference || ""}. You'll receive it shortly.`);

        }else if(category === "deposit"){
          let amt = res.data?.amount || data?.total;
          let email = res.data?.email || "user@lendro.ng";
          let txrefno = res.data?.transaction_ref;

          SquadPay(email, amt, txrefno).then(ref => {
              VerifyFund({refno:ref, total:amt, setWalletData, setMaxCUPoint, setTransactions});
          }).catch(err => { console.log("SquadPay closed/error:", err); });
        }
      }else{
        const m = res?.message || res?.data?.message;
        console.log(res?.message, res?.data, res?.param);
        alert(m || `Request failed. Please try again.`);
      }

    } catch (err) {
      console.error("API error:", err);
      alert("Network error. Please check your connection and try again.");
    } finally{
      setPopupOpen({open:false, data:{}});
      setBusy(false);
    }
};

////////////////////////////////////

const PopUpModal = ({ popupOpen, setPopupOpen, onClose, onSubmit, wallet, services,setBusy,setWalletData,setMaxCUPoint,setTransactions }) => {

    const data = popupOpen?.data;
    if(!data) return null;

    return e("div", null,
    /* Backdrop */
    popupOpen?.open && e("div", {key: "backdrop", className: "fixed inset-0 bg-black/75 z-40" }),
    e("div", { key: "modal", className: `fixed bottom-0 left-0 right-0 z-50 bg-white rounded-t-2xl p-5 pb-8 min-h-[40vh] max-h-[90vh] overflow-y-auto transform transition-transform duration-300 ease-out ${popupOpen?.open ? "translate-y-0" : "translate-y-full"}` },

      e("button", {key: "close", onClick: ()=>setPopupOpen({open:false,data:{}}), className: "absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition" },
        e("i", { "data-lucide": "x-circle", className: "w-6 h-6" })
      ),
      e("div", { key: "handle", className: "w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4" }),

      // Deposit
      ((data?.dwat === "deposit") && e(depositForm, {data,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),

      // Buy Airtime
      ((data?.dwat === "buyairtime") && e(billerForm, {data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),

      // Buy Data (triggered from PageItems item click)
      ((data?.dwat === "buydata") && e(billerForm, {data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),

    )
  );
};

/////////////////

const billerForm = ({data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})=>{
  const storedPhone = getStorage("lendro.userphone") || "";
  const [myphone, setMyPhone] = useState(storedPhone);
  const balance = wallet?.balance || 0;

  const dwat     = data?.dwat;
  const item     = data?.item; // set when opening from PageItems (data plan)
  const category = (dwat === "buyairtime") ? "airtime" : "data";
  let   catTitle = (dwat === "buyairtime") ? "Airtime" : "Data";

  // For data purchases the amount is fixed to the plan's price
  const isFixedPrice = (dwat === "buydata") && item?.amount != null;
  const [amount, setAmount] = useState(isFixedPrice ? item.amount : "");

  // The DB service id — for data it comes from the item; for airtime from the network row
  // data.serviceid is set by the Services component when the network icon is clicked
  const serviceId = item?.id ?? data?.serviceid ?? null;

  if(!dwat) return e("div",{className:"w-full text-lg text-center p-5"},
    e("span",{"data-lucide": "x-circle", className:"w-8 h-8 text-lg text-red-600 mb-3 mx-auto"}),
    e("div",{className:"w-full text-lg p-5"},"Sorry, something is missing, reload and try again!")
  );

  const planLabel = item
    ? `${item.biller_name} — ${item.validity_period} day${item.validity_period > 1 ? "s" : ""}`
    : (data?.biller ? flwNames[data.biller] || data.biller : "");

  return e("div",{},
    e("h3", { key: "title", className: "text-lg font-semibold mb-1" }, `Buy ${catTitle}`),

    planLabel && e("p", { className: "text-sm text-indigo-600 font-medium mb-3" }, planLabel),

    // Phone input
    e("div", { key: "rphone", className: "flex items-center w-full mb-2" },
        e("span", { className: "px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg text-md"}, "+234"),
        e("input",{type:"tel", placeholder:"8012345678", name:"rphone", maxLength:11,
          className:"w-full px-3 py-2 rounded-r-lg bg-white border border-gray-400 border-l-0 focus:outline-none",
          value:myphone || "",
          onChange: (ev) => setMyPhone(ev.target.value.replace(/\D/g, "").replace(/^0+/, "").slice(0, 10)) }),
    ),

    // Amount input — read-only for fixed-price data plans, editable for airtime
    e("div", { key: "ramount", className: "flex items-center w-full mb-1" },
        e("span", { className: "px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg text-md"}, "₦"),
        e("input",{type:"number", step:1, min:100,
          placeholder: isFixedPrice ? "" : "Enter amount",
          name:"ramount",
          className:`w-full px-3 py-2 rounded-r-lg bg-white border border-gray-400 border-l-0 focus:outline-none ${isFixedPrice ? "text-gray-500 cursor-not-allowed" : ""}`,
          value: amount,
          readOnly: isFixedPrice,
          onChange: (ev) => !isFixedPrice && setAmount(ev.target.value)
        }),
    ),
    e("div", { className: "mb-3 text-sm text-gray-500"},
      isFixedPrice
        ? `Fixed plan price: ${formatCurrency(item.amount)}`
        : `Allowed limit: ${formatCurrency(100)} - ${formatCurrency(data?.maxPrice || 50000)}`
    ),

    e("button", { key: "buy", type: "button",
      className: "w-full bg-indigo-600 text-white px-4 py-3 rounded-lg font-semibold active:scale-95 transition mb-5",
      onClick:()=>Buy({
        category,
        data:{
          catTitle,
          service_id: serviceId,
          myphone,
          amount: parseFloat(amount) || 0,
          balance
        },
        setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions
      })
    }, `Pay${(parseFloat(amount) > 0) ? " "+formatCurrency(parseFloat(amount)):""}`),

    // Predefined quick-pick amounts — airtime only
    e("div", { key: "preamounts", className: "flex flex-wrap items-center gap-4 w-full mb-5" },
      (dwat === "buyairtime") && [100,200,300,400,500,700,1000,2000,3000].map((price,i) => {
        return e("span", { key:i,
          className: "flex-[0_0_calc(33.333%-1rem)] px-3 py-2 bg-gray-500/20 rounded-lg text-lg text-center cursor-pointer",
          onClick:()=>setAmount(price)
        }, `${formatCurrency(price,false)}`)
      }),
    ),
  )
};

//Deposit module......
const depositForm = ({data,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})=>{
  const [amount, setAmount] = useState("");
  const [fee, setFee]       = useState(0);
  const [total, setTotal]   = useState(0);

  const calcAmount = (amt)=>{
    if(!amt) { setAmount(""); setFee(0); setTotal(0); return; }
    let f = (parseFloat(DWFee) * parseFloat(amt)).toFixed(0);
    let t = parseFloat(amt) + parseFloat(f);
    setAmount(amt); setFee(f); setTotal(t);
  };

  return e("div",{},
    e("h3", { key: "title", className: "text-lg font-semibold mb-3" }, `Deposit Fund`),
    e("div", { key: "amount", className: "flex items-center w-full mb-1" },
        e("span", { className: "px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg text-md"}, "₦"),
        e("input",{type:"number",step:1,min:100, placeholder:"Enter amount",name:"ramount",
          className:"w-full px-3 py-2 rounded-r-lg bg-white border border-gray-400 border-l-0 focus:outline-none",
          value: amount, onChange: (ev) => calcAmount(ev.target.value)}),
    ),
    e("div", { className: "mb-3 text-sm text-gray-500"}, `Minimum allowed: ${formatCurrency(100)}`),

    (amount > 0) && e("div", { className: "mb-3 text-sm text-gray-900 bg-yellow-100 p-3 rounded-md text-center border-2"},
      e("span", { className: "mb-1 text-gray-900"},
        `Pay ${formatCurrency(total)} to deposit (${formatCurrency(fee)} fee included).`
      ),
    ),

    e("button", { key: "buy", type: "button",
      className: "w-full bg-indigo-600 text-white px-4 py-3 rounded-lg font-semibold active:scale-95 transition mb-5",
      onClick:()=>Buy({category:"deposit",data:{amount,fee,total},setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions})
    }, `Deposit`),

    e("div", { key: "preamounts", className: "flex flex-wrap items-center gap-4 w-full mb-5" },
      [100,200,500,1000,2000,3000,4000,5000,10000].map((price,i) => {
        return e("span", { key:i,
          className: "flex-[0_0_calc(33.333%-1rem)] px-3 py-2 bg-gray-500/20 rounded-lg text-lg text-center cursor-pointer",
          onClick:()=>calcAmount(price)
        }, `${formatCurrency(price,false)}`)
      }),
    ),
  )
}
