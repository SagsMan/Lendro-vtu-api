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

    }else if(category === "cable"){
      if(!data?.smartcard){ alert("Smart card / IUC number is required!"); return; }
      if(!data?.plan_id)  { alert("Please select a plan!"); return; }
      if(data?.balance < 100){ alert("Insufficient balance in your wallet."); return; }
      url  = "/client/order.php";
      body = {service_id:data?.plan_id, smartcard_number:data?.smartcard, phone:data?.myphone||"00000000000", idempotency_key:idempotencyKey};

    }else if(category === "electricity"){
      if(!data?.meter_number){ alert("Meter number is required!"); return; }
      if(data?.amount < 100) { alert("Minimum allowed is ₦100"); return; }
      if(data?.balance < 100){ alert("Insufficient balance in your wallet."); return; }
      url  = "/client/order.php";
      body = {service_id:data?.service_id, meter_number:data?.meter_number, meter_type:data?.meter_type||"prepaid", phone:data?.myphone||"00000000000", amount:data?.amount, idempotency_key:idempotencyKey};

    }else if(category === "education"){
      if(!data?.quantity||data?.quantity<1){ alert("Quantity must be at least 1!"); return; }
      if(data?.balance < 100){ alert("Insufficient balance in your wallet."); return; }
      url  = "/client/order.php";
      body = {service_id:data?.service_id, quantity:data?.quantity||1, phone:data?.myphone||"00000000000", idempotency_key:idempotencyKey};

    }else if(!category){ return; }

    try{
      setBusy(true);
      const res = await apiFetch(url, body);

      if (res?.status === "success" || res?.status === "processing") {
        if(res.data?.wallet){
            saveStorage("lendro.wallet",res.data?.wallet,{merge:true});
            setWalletData(res.data?.wallet);
            setMaxCUPoint(res.data?.wallet.maxusage);
        } else {
            // Auto-refresh wallet balance from server after any successful purchase
            try {
              const wr = await apiFetch(apiEndPoint+"/client/wallet");
              if(wr?.status==="success" && wr?.data?.wallet){
                saveStorage("lendro.wallet",wr.data.wallet,{merge:true});
                setWalletData(wr.data.wallet);
                setMaxCUPoint(wr.data.wallet.maxusage);
              }
            } catch(_){}
        }
        if(category === "airtime"){
          alert(`${formatCurrency(data?.amount)} airtime request submitted. Ref: ${res?.reference || ""}`);

        }else if(category === "cable"){
          alert("Cable TV subscription submitted. Ref: "+(res?.reference||"")+". Token will be sent to your phone.");
        }else if(category === "electricity"){
          alert("Electricity token submitted. Ref: "+(res?.reference||"")+". Token/units sent to your phone.");
        }else if(category === "education"){
          alert("Exam PIN request submitted. Ref: "+(res?.reference||"")+". PIN will be sent to your phone.");
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

      // Buy Cable TV
      ((data?.dwat === "buycable")       && e(cableForm,       {data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),
      // Buy Electricity
      ((data?.dwat === "buyelectricity") && e(electricityForm, {data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),
      // Buy Education PIN
      ((data?.dwat === "buyeducation")   && e(educationForm,   {data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions})),
      // KYC / Generate Virtual Account
      ((data?.dwat === "kyc") && e(kycForm, {data,setBusy,setPopupOpen})),

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

// KYC / Generate Virtual Account form
// User MUST provide NIN (11 digits) OR BVN (11 digits) — or both.
// After successful verification, SquadCo auto-creates a permanent virtual account.
const kycForm = ({data, setBusy, setPopupOpen}) => {
  const [nin,       setNin]       = useState("");
  const [bvn,       setBvn]       = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName,  setLastName]  = useState("");
  const [dob,       setDob]       = useState("");
  const [loading,   setLoading]   = useState(false);
  const [result,    setResult]    = useState(null); // holds success response
  const [errMsg,    setErrMsg]    = useState("");

  const isNinValid = nin.length === 11 && /^\d{11}$/.test(nin);
  const isBvnValid = bvn.length === 11 && /^\d{11}$/.test(bvn);
  const canSubmit  = isNinValid || isBvnValid;

  const handleSubmit = async () => {
    setErrMsg("");
    if (!canSubmit) {
      setErrMsg("Please enter a valid 11-digit NIN or BVN before continuing.");
      return;
    }
    setLoading(true);
    setBusy(true);
    try {
      const body = {};
      if (nin)       body.nin        = nin;
      if (bvn)       body.bvn        = bvn;
      if (firstName) body.first_name = firstName;
      if (lastName)  body.last_name  = lastName;
      if (dob)       body.dob        = dob;

      const res = await apiFetch("/auth/kyc.php", body);

      if (res?.status === "success") {
        // Save kyc verified state locally so wallet page can reflect it
        saveStorage("lendro.kyc_status", "verified");
        if (res?.virtual_account?.account_number) {
          saveStorage("lendro.virtual_account", res.virtual_account);
        }
        setResult(res);
      } else if (res?.status === "already_verified") {
        setErrMsg("Your identity has already been verified.");
        setResult({ status:"success", message: res.message, virtual_account: null });
      } else {
        setErrMsg(res?.message || "Verification failed. Please check your details and try again.");
      }
    } catch (err) {
      setErrMsg("Network error. Please check your connection and try again.");
    } finally {
      setLoading(false);
      setBusy(false);
    }
  };

  // ── Success screen ────────────────────────────────────────────────────────
  if (result?.status === "success" && result?.virtual_account?.account_number) {
    const va = result.virtual_account;
    return e("div", {className:"space-y-4"},
      e("div", {className:"flex flex-col items-center gap-2 mb-2"},
        e("div", {className:"w-14 h-14 rounded-full bg-green-100 flex items-center justify-center"},
          e("i", {"data-lucide":"check-circle-2", className:"w-8 h-8 text-green-600"})
        ),
        e("h3", {className:"text-lg font-bold text-gray-900"}, "Identity Verified!"),
        e("p",  {className:"text-sm text-gray-500 text-center"}, "Your virtual account has been created. Use it to fund your wallet by bank transfer.")
      ),
      e("div", {className:"bg-indigo-50 border border-indigo-200 rounded-xl p-4 space-y-3"},
        e("p", {className:"text-xs text-gray-500 uppercase tracking-wide font-semibold"}, "Virtual Account Details"),
        e("div", {className:"flex justify-between items-center"},
          e("span", {className:"text-sm text-gray-600"}, "Bank"),
          e("span", {className:"font-semibold text-gray-900"}, va.bank_name || "GTBank")
        ),
        e("div", {className:"flex justify-between items-center"},
          e("span", {className:"text-sm text-gray-600"}, "Account Number"),
          e("span", {className:"font-bold text-xl text-indigo-700 tracking-widest"}, va.account_number)
        ),
        va.account_name && e("div", {className:"flex justify-between items-center"},
          e("span", {className:"text-sm text-gray-600"}, "Account Name"),
          e("span", {className:"font-semibold text-gray-900"}, va.account_name)
        ),
      ),
      e("p", {className:"text-xs text-gray-400 text-center"}, "Transfers to this account credit your Lendro wallet instantly."),
      e("button", {
        className:"w-full bg-indigo-600 text-white py-3 rounded-xl font-semibold mt-2",
        onClick: ()=>setPopupOpen({open:false,data:{}})
      }, "Done")
    );
  }

  // ── KYC form ──────────────────────────────────────────────────────────────
  return e("div", {className:"space-y-4"},

    e("div", {className:"flex flex-col items-center gap-1 mb-2"},
      e("div", {className:"w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center"},
        e("i", {"data-lucide":"shield-check", className:"w-7 h-7 text-indigo-600"})
      ),
      e("h3", {className:"text-lg font-bold text-gray-900"}, "Verify Your Identity"),
      e("p",  {className:"text-sm text-gray-500 text-center"},
        "Enter your NIN or BVN to verify your identity and generate a personal bank account for instant wallet funding."
      )
    ),

    // NIN field
    e("div", {className:"space-y-1"},
      e("label", {className:"text-sm font-medium text-gray-700"}, "NIN (National Identification Number)"),
      e("input", {
        type:"tel", maxLength:11, placeholder:"Enter your 11-digit NIN",
        className:`w-full px-3 py-2.5 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 ${nin && !isNinValid ? "border-red-400" : "border-gray-300"}`,
        value: nin,
        onChange: ev => setNin(ev.target.value.replace(/\D/g,"").slice(0,11))
      }),
      nin && !isNinValid && e("p", {className:"text-xs text-red-500"}, "NIN must be exactly 11 digits."),
      isNinValid   && e("p", {className:"text-xs text-green-600"}, "✓ NIN looks good.")
    ),

    // BVN field
    e("div", {className:"space-y-1"},
      e("label", {className:"text-sm font-medium text-gray-700"}, "BVN (Bank Verification Number)"),
      e("input", {
        type:"tel", maxLength:11, placeholder:"Enter your 11-digit BVN",
        className:`w-full px-3 py-2.5 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 ${bvn && !isBvnValid ? "border-red-400" : "border-gray-300"}`,
        value: bvn,
        onChange: ev => setBvn(ev.target.value.replace(/\D/g,"").slice(0,11))
      }),
      bvn && !isBvnValid && e("p", {className:"text-xs text-red-500"}, "BVN must be exactly 11 digits."),
      isBvnValid   && e("p", {className:"text-xs text-green-600"}, "✓ BVN looks good.")
    ),

    // Optional fields — First Name, Last Name, Date of Birth
    e("details", {className:"group"},
      e("summary", {className:"cursor-pointer text-sm text-indigo-600 font-medium list-none flex items-center gap-1"},
        e("i", {"data-lucide":"chevron-right", className:"w-4 h-4 group-open:rotate-90 transition-transform"}),
        "Add personal details (optional but recommended)"
      ),
      e("div", {className:"mt-3 space-y-3"},
        e("div", {className:"flex gap-2"},
          e("div", {className:"flex-1 space-y-1"},
            e("label", {className:"text-xs font-medium text-gray-600"}, "First Name"),
            e("input", {type:"text", placeholder:"e.g. Sagiru",
              className:"w-full px-3 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 text-sm",
              value: firstName, onChange: ev => setFirstName(ev.target.value)})
          ),
          e("div", {className:"flex-1 space-y-1"},
            e("label", {className:"text-xs font-medium text-gray-600"}, "Last Name"),
            e("input", {type:"text", placeholder:"e.g. Garba",
              className:"w-full px-3 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 text-sm",
              value: lastName, onChange: ev => setLastName(ev.target.value)})
          )
        ),
        e("div", {className:"space-y-1"},
          e("label", {className:"text-xs font-medium text-gray-600"}, "Date of Birth"),
          e("input", {type:"date",
            className:"w-full px-3 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-400 text-sm",
            value: dob, onChange: ev => setDob(ev.target.value)})
        )
      )
    ),

    // Validation error message
    errMsg && e("div", {className:"bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3"},
      e("i", {"data-lucide":"alert-circle", className:"inline w-4 h-4 mr-1"}),
      errMsg
    ),

    // Info note
    e("p", {className:"text-xs text-gray-400"},
      "Your details are encrypted and used only for identity verification. We do not share them with third parties."
    ),

    // Submit button — disabled until NIN or BVN is valid
    e("button", {
      type:"button",
      disabled: !canSubmit || loading,
      className:`w-full py-3 rounded-xl font-semibold transition ${
        canSubmit && !loading
          ? "bg-indigo-600 hover:bg-indigo-500 text-white active:scale-95"
          : "bg-gray-200 text-gray-400 cursor-not-allowed"
      }`,
      onClick: handleSubmit
    }, loading ? "Verifying…" : "Verify & Generate Account")
  );
};


//////////////////// Cable TV Form  (with smart card verification)
const cableForm = ({data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions}) => {
  const balance  = wallet?.balance || 0;
  const provider = data?.provider     || "";
  const provName = data?.providerName || provider;

  const [smartcard,  setSmartcard]  = useState("");
  const [myphone,    setMyPhone]    = useState(getStorage("lendro.userphone") || "");
  const [planId,     setPlanId]     = useState("");
  const [verified,   setVerified]   = useState(null);  // {name, package, due_date}
  const [verifying,  setVerifying]  = useState(false);
  const [verifyErr,  setVerifyErr]  = useState("");

  const cablePlans = {
    startimes:[
      {id:"startimes_nova",    name:"Nova",         price:1900},
      {id:"startimes_basic",   name:"Basic",        price:2600},
      {id:"startimes_smart",   name:"Smart",        price:3600},
      {id:"startimes_classic", name:"Classic",      price:4500},
      {id:"startimes_super",   name:"Super",        price:6000},
    ],
    dstv:[
      {id:"dstv_padi",         name:"Padi",         price:2950},
      {id:"dstv_yanga",        name:"Yanga",        price:3500},
      {id:"dstv_confam",       name:"Confam",       price:6200},
      {id:"dstv_compact",      name:"Compact",      price:15700},
      {id:"dstv_compact_plus", name:"Compact+",     price:25000},
      {id:"dstv_premium",      name:"Premium",      price:37000},
    ],
    gotv:[
      {id:"gotv_smallie",      name:"Smallie",      price:1575},
      {id:"gotv_jinja",        name:"Jinja",        price:2715},
      {id:"gotv_jolli",        name:"Jolli",        price:4115},
      {id:"gotv_max",          name:"Max",          price:6200},
      {id:"gotv_supa",         name:"Supa",         price:9300},
    ],
  };
  const plans        = cablePlans[provider] || [];
  const selectedPlan = plans.find(p=>p.id===planId);

  const handleVerify = async () => {
    if (!smartcard || smartcard.length < 5) { setVerifyErr("Enter a valid smart card / IUC number."); return; }
    setVerifying(true); setVerifyErr(""); setVerified(null);
    try {
      const res = await apiFetch("/client/verify.php", {type:"cable", provider, smartcard});
      if (res?.status === "success") {
        setVerified(res.data);
      } else {
        setVerifyErr(res?.message || "Verification failed. Check the number and try again.");
      }
    } catch(err) {
      setVerifyErr("Network error. Please try again.");
    }
    setVerifying(false);
  };

  return e("div",{},
    e("h3",{className:"text-lg font-semibold mb-1"},"Buy "+provName+" Subscription"),
    e("p", {className:"text-sm text-gray-500 mb-3"},"Verify your smart card / IUC number first."),

    // Step 1: Smart card input + verify button
    e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Smart Card / IUC Number"),
      e("div",{className:"flex gap-2"},
        e("input",{type:"tel",placeholder:"e.g. 7042987654",maxLength:10,
          className:"flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400",
          value:smartcard, onChange:ev=>{setSmartcard(ev.target.value.replace(/[^0-9]/g,"").slice(0,10)); setVerified(null); setVerifyErr("");}}),
        e("button",{
          className:"px-4 py-2 rounded-lg text-sm font-semibold transition "+(verified?"bg-green-500 text-white":"bg-indigo-600 text-white active:scale-95"),
          onClick: verified ? null : handleVerify,
          disabled: verifying
        }, verifying ? "..." : verified ? "✓ Verified" : "Verify")
      )
    ),

    // Verify error
    verifyErr && e("div",{className:"bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-3 text-sm text-red-700"},verifyErr),

    // Verified customer info card
    verified && e("div",{className:"bg-green-50 border border-green-200 rounded-lg p-3 mb-3"},
      e("p",{className:"text-xs text-green-600 font-semibold uppercase tracking-wide mb-1"},"Verified Customer"),
      e("p",{className:"text-sm font-bold text-gray-900"},verified.name),
      verified.package && e("p",{className:"text-xs text-gray-500"},"Current plan: "+verified.package),
      verified.due_date && e("p",{className:"text-xs text-gray-500"},"Expires: "+verified.due_date)
    ),

    // Step 2: Plan select (show only after verify)
    verified && e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Select New Plan"),
      e("select",{className:"w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none",
        value:planId, onChange:ev=>setPlanId(ev.target.value)},
        e("option",{value:""},"-- Choose Plan --"),
        plans.map(p=>e("option",{key:p.id,value:p.id},p.name+" — "+formatCurrency(p.price)))
      )
    ),

    // Phone number
    verified && e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Phone Number"),
      e("div",{className:"flex items-center"},
        e("span",{className:"px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg"},"+234"),
        e("input",{type:"tel",placeholder:"8012345678",maxLength:11,
          className:"w-full px-3 py-2 border border-gray-300 rounded-r-lg border-l-0 focus:outline-none",
          value:myphone, onChange:ev=>setMyPhone(ev.target.value.replace(/[^0-9]/g,"").replace(/^0+/,"").slice(0,10))})
      )
    ),

    // Price summary
    verified && selectedPlan && e("div",{className:"bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-3 text-sm"},
      e("span",{className:"text-gray-700"},"Total: "),
      e("strong",{className:"text-indigo-700"},formatCurrency(selectedPlan.price))
    ),

    // Pay button (only when verified + plan selected)
    verified && e("button",{
      className:"w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold active:scale-95 transition "+(planId?"":"opacity-50 pointer-events-none"),
      onClick:()=> planId && Buy({
        category:"cable",
        data:{service_id:planId, smartcard, plan_id:planId, myphone, balance},
        setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions
      })
    },"Pay"+(selectedPlan?" "+formatCurrency(selectedPlan.price):""))
  );
};

//////////////////// Electricity Form  (with meter number verification)
const electricityForm = ({data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions}) => {
  const balance   = wallet?.balance || 0;
  const discoCode = data?.provider     || "";
  const discoName = data?.providerName || discoCode;

  const [meterNo,   setMeterNo]   = useState("");
  const [meterType, setMeterType] = useState("prepaid");
  const [amount,    setAmount]    = useState("");
  const [myphone,   setMyPhone]   = useState(getStorage("lendro.userphone") || "");
  const [verified,  setVerified]  = useState(null);   // {name, address}
  const [verifying, setVerifying] = useState(false);
  const [verifyErr, setVerifyErr] = useState("");

  const handleVerify = async () => {
    if (!meterNo || meterNo.length < 5) { setVerifyErr("Enter a valid meter number."); return; }
    setVerifying(true); setVerifyErr(""); setVerified(null);
    try {
      const res = await apiFetch("/client/verify.php", {type:"electricity", provider:discoCode, meter_number:meterNo, meter_type:meterType});
      if (res?.status === "success") {
        setVerified(res.data);
      } else {
        setVerifyErr(res?.message || "Verification failed. Check the meter number and try again.");
      }
    } catch(err) {
      setVerifyErr("Network error. Please try again.");
    }
    setVerifying(false);
  };

  return e("div",{},
    e("h3",{className:"text-lg font-semibold mb-1"},"Pay "+discoName+" Electricity"),
    e("p", {className:"text-sm text-gray-500 mb-3"},"Verify your meter number first, then enter the amount."),

    // Meter Type toggle (needed before verify)
    e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Meter Type"),
      e("div",{className:"flex gap-3"},
        ["prepaid","postpaid"].map(t=>
          e("button",{key:t,
            className:"flex-1 py-2 rounded-lg border text-sm font-semibold transition "+(meterType===t?"bg-indigo-600 text-white border-indigo-600":"bg-white text-gray-600 border-gray-200"),
            onClick:()=>{setMeterType(t); setVerified(null); setVerifyErr("");}},
          t.charAt(0).toUpperCase()+t.slice(1))
        )
      )
    ),

    // Meter number + verify
    e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Meter Number"),
      e("div",{className:"flex gap-2"},
        e("input",{type:"tel",placeholder:"e.g. 0101234567890",maxLength:15,
          className:"flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400",
          value:meterNo, onChange:ev=>{setMeterNo(ev.target.value.replace(/[^0-9]/g,"").slice(0,15)); setVerified(null); setVerifyErr("");}}),
        e("button",{
          className:"px-4 py-2 rounded-lg text-sm font-semibold transition "+(verified?"bg-green-500 text-white":"bg-indigo-600 text-white active:scale-95"),
          onClick: verified ? null : handleVerify,
          disabled: verifying
        }, verifying ? "..." : verified ? "✓ OK" : "Verify")
      )
    ),

    // Verify error
    verifyErr && e("div",{className:"bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-3 text-sm text-red-700"},verifyErr),

    // Verified customer info card
    verified && e("div",{className:"bg-green-50 border border-green-200 rounded-lg p-3 mb-3"},
      e("p",{className:"text-xs text-green-600 font-semibold uppercase tracking-wide mb-1"},"Verified Meter"),
      e("p",{className:"text-sm font-bold text-gray-900"},verified.name),
      verified.address && e("p",{className:"text-xs text-gray-500"},verified.address)
    ),

    // Amount input (only after verify)
    verified && e("div",{className:"mb-1"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Amount to Purchase (₦)"),
      e("div",{className:"flex items-center"},
        e("span",{className:"px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg"},"₦"),
        e("input",{type:"number",min:100,step:100,placeholder:"Enter amount",
          className:"w-full px-3 py-2 border border-gray-300 rounded-r-lg border-l-0 focus:outline-none",
          value:amount, onChange:ev=>setAmount(ev.target.value)})
      )
    ),
    verified && e("div",{className:"mb-3 text-xs text-gray-500"},"Minimum: ₦100"),

    // Quick amount buttons
    verified && e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Phone Number"),
      e("div",{className:"flex items-center"},
        e("span",{className:"px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg"},"+234"),
        e("input",{type:"tel",placeholder:"8012345678",maxLength:11,
          className:"w-full px-3 py-2 border border-gray-300 rounded-r-lg border-l-0 focus:outline-none",
          value:myphone, onChange:ev=>setMyPhone(ev.target.value.replace(/[^0-9]/g,"").replace(/^0+/,"").slice(0,10))})
      )
    ),

    verified && e("div",{className:"flex flex-wrap gap-2 mb-3"},
      [1000,2000,3000,5000,10000,20000].map(p=>
        e("button",{key:p,className:"px-3 py-1.5 text-xs rounded-lg bg-gray-100 hover:bg-indigo-100 font-medium",
          onClick:()=>setAmount(p)},formatCurrency(p))
      )
    ),

    verified && e("button",{
      className:"w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold active:scale-95 transition "+(amount&&parseFloat(amount)>=100?"":"opacity-50 pointer-events-none"),
      onClick:()=>amount&&parseFloat(amount)>=100&&Buy({
        category:"electricity",
        data:{service_id:discoCode, meter_number:meterNo, meter_type:meterType, myphone, amount:parseFloat(amount)||0, balance},
        setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions
      })
    },"Pay"+(amount?" "+formatCurrency(parseFloat(amount)||0):""))
  );
};

//////////////////// Education (Exam PIN) Form
const educationForm = ({data,services,wallet,setBusy,setWalletData,setMaxCUPoint,setPopupOpen,setTransactions}) => {
  const balance  = wallet?.balance || 0;
  const examCode = data?.provider     || "";
  const examName = data?.providerName || examCode;
  const [quantity, setQuantity] = useState(1);
  const [myphone,  setMyPhone]  = useState(getStorage("lendro.userphone") || "");

  const examPrices = {waec:4000, jamb:7000, neco:3500, nabteb:3000};
  const unitPrice  = examPrices[examCode] || 3500;
  const total      = unitPrice * quantity;

  return e("div",{},
    e("h3",{className:"text-lg font-semibold mb-1"},"Buy "+examName+" Exam PIN"),
    e("p", {className:"text-sm text-gray-500 mb-3"},"Unit price: "+formatCurrency(unitPrice)+" per PIN"),

    e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Quantity"),
      e("div",{className:"flex items-center gap-4"},
        e("button",{className:"w-10 h-10 bg-gray-100 rounded-lg font-bold text-xl",onClick:()=>setQuantity(q=>Math.max(1,q-1))},"-"),
        e("span",{className:"text-xl font-bold w-8 text-center"},quantity),
        e("button",{className:"w-10 h-10 bg-gray-100 rounded-lg font-bold text-xl",onClick:()=>setQuantity(q=>Math.min(10,q+1))},"+")
      )
    ),

    e("div",{className:"mb-3"},
      e("label",{className:"text-sm font-medium text-gray-700 block mb-1"},"Phone Number"),
      e("div",{className:"flex items-center"},
        e("span",{className:"px-3 py-2 bg-gray-500/20 border border-gray-400 border-r-0 rounded-l-lg"},"+234"),
        e("input",{type:"tel",placeholder:"8012345678",maxLength:11,
          className:"w-full px-3 py-2 border border-gray-300 rounded-r-lg border-l-0 focus:outline-none",
          value:myphone, onChange:ev=>setMyPhone(ev.target.value.replace(/[^0-9]/g,"").replace(/^0+/,"").slice(0,10))})
      )
    ),

    e("div",{className:"bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-3 text-sm"},
      e("span",{className:"text-gray-700"},quantity+" PIN"+(quantity>1?"s":"")+": "),
      e("strong",{className:"text-indigo-700"},formatCurrency(total))
    ),

    e("button",{className:"w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold active:scale-95 transition",
      onClick:()=>Buy({
        category:"education",
        data:{service_id:examCode, quantity, myphone, balance},
        setWalletData,setMaxCUPoint,setBusy,setPopupOpen,setTransactions
      })
    },"Pay "+formatCurrency(total))
  );
};
