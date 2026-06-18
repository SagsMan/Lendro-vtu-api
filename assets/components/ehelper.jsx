window.ExpectedTotalCS = 200; //Expected Total Credit Score 
window.UPointPerPurchase = 5;
window.CTPointPerMember = 5
window.DWFee = 0.03; //Deposit and withdrawal fee...3%
window.MaxKYCPoint = 80;
//window.globaluid = null;
window.myPlan = null;
window.PlanChoice = {};
window.osharePercent = 25;

window.SquadPKey = "sandbox_pk_a696877073b5c7c3fc77cb1b5a9e8880d94e52bb0447";
window.baseUrl =  window.location.origin + window.location.pathname.split("/").slice(0, 2).join("/"); //origin only, localhost:0,2, online subfolder: 0,3
window.apiEndPoint = baseUrl + "/api/v1";

// [0-planname,1-weight,2-price,3-slots,4-data_plan,5-duration,6-points,7-adpoints]..
window.Plans = {"1":["Basic Plan",null,500,null,"500MB","(1day)",50,0],"2":["Bronze Plan",0.1,2000,2000,"1GB","(1day)",50,30],"3":["Platinum Plan",0.2,4000,2000,"1GB","(7days)",50,60],"4":["Diamond Plan",0.3,5000,2000,"2GB","(7days)",50,80],"7":"Community Partner"};

window.flwImages = {"mtn":"assets/images/mtn.jpg","airtel":"assets/images/airtel.jpg","glo":"assets/images/glo.jpg","9mobile":"assets/images/9mobile.jpg","etisalat":"assets/images/etisalat.jpg","mtn-data":"assets/images/mtn.jpg","airtel-data":"assets/images/airtel.jpg","glo-data":"assets/images/glo.jpg","9mobile-data":"assets/images/9mobile.jpg","etisalat-data":"assets/images/etisalat.jpg","smile-direct":"assets/images/smile.jpg","spectranet":"assets/images/spectranet.jpg","glo-sme-data":"assets/images/glo.jpg"};

window.flwNames = {"Airtime Recharge":"Airtime","Mobile Data Service":"Data","Data Services":"Data","Cable Bill Payment":"Cable TV","Cable TV Bundle":"Cable TV","Cable TV Subscription":"Cable TV","TV Subscription":"Cable TV","Electricity Bill":"Electricity","education":"Education","Education":"Education","Transport and Logistics":"Transport","insurance":"Insurance","Religious Institutions":"Religious","Schools & Professional Bodies":"Schools","MTN Airtime VTU":"MTN","Airtel Airtime VTU":"Airtel","GLO Airtime VTU":"GLO","9mobile Airtime VTU":"9mobile",
  "mtn-data":"MTN","glo-data":"GLO","airtel-data":"Airtel","9mobile-data":"9mobile","etisalat-data":"9mobile","smile-direct":"Smile","spectranet":"Spectranet","glo-sme-data":"GLO (SME)"};

window.flwIcons = {"airtime":"smartphone","data":"wifi","tv-subscription":"tv","INTSERVICE":"globe","electricity-bill":"zap","education":"graduation-cap","insurance":"shield-check","TRANSLOG":"truck","DEALPAY":"credit-card","RELINST":"landmark","SCHPB":"graduation-cap","cable":"tv","bundle":"tv","electricity":"zap","waec":"graduation-cap","jamb":"graduation-cap",
  "mtn":"smartphone","airtel":"smartphone","glo":"smartphone","9mobile":"smartphone","etisalat":"smartphone"
};

//"airtime":"bg-amber-500","data":"bg-sky-500","tv-subscription":"bg-purple-500","INTSERVICE":"bg-sky-500", "electricity-bill":"bg-indigo-600","TAX":"bg-gray-700","insurance":"bg-emerald-500","education":"bg-blue-500","RELINST":"bg-violet-500","SCHPB":"bg-blue-500",

window.flwColors = {"airtime":"bg-amber-500","data":"bg-indigo-500","tv-subscription":"bg-purple-500","INTSERVICE":"bg-indigo-500","electricity-bill":"bg-yellow-600","insurance":"bg-emerald-500","education":"bg-blue-600","cable":"bg-purple-500","bundle":"bg-purple-500","electricity":"bg-yellow-600","waec":"bg-blue-600","jamb":"bg-blue-600",
  "mtn":"bg-amber-500","mtn-data":"bg-amber-500",
  "airtel":"bg-red-500","airtel-data":"bg-red-500",
  "glo":"bg-green-600","glo-data":"bg-green-600","glo-sme-data":"bg-green-600",
  "9mobile":"bg-lime-600","9mobile-data":"bg-lime-600",
  "etisalat":"bg-lime-500","etisalat-data":"bg-lime-500",
  "smile":"bg-indigo-500","spectranet":"bg-indigo-500"
};
////////////////////////

const ErrorPage = ({setPage,handleLogout})=>{
  return e("div", { className: "app-page" },
            e("div", { className: "px-3 min-h-[calc(100vh-80px)] flex items-center justify-center" }, 
                e( "div", { className: "bg-gradient-to-br from-[#4F46E5] to-[#6366F1] rounded-3xl p-5 shadow-lg shadow-indigo-200/50 text-white text-center flex flex-col items-center gap-4" }, 
                  e( "div", { className: "w-14 h-14 bg-yellow-400 rounded-full flex items-center justify-center" }, 
                    e("i", { "data-lucide": "info", className: "w-8 h-8 text-[#4F46E5]" }) 
                  ), 
                  e( "h3", { className: "font-bold text-3xl" }, `Something is wrong!` ), 
                  e( "p", { className: "text-indigo-100 text-md leading-relaxed max-w-sm" }, `Technical issue occurred, refresh the page or log out to try again. If the issue persist, do not hesitate to contact us for assistance`, 
                    e( "span", { className: "flex items-center justify-center gap-3 mt-5" }, 
                      e( "span", { className: "text-yellow-400 font-semibold cursor-pointer hover:underline",onClick:()=>setPage("home") }, "Home" ),
                      e( "span", { className: "text-white/20" }, "|" ),
                      e( "span", { className: "text-yellow-400 font-semibold cursor-pointer hover:underline",onClick:()=>setPage("services") }, "Services" ) ,
                      e( "span", { className: "text-white/20" }, "|" ),
                      e( "span", { className: "text-yellow-400 font-semibold cursor-pointer hover:underline",onClick: handleLogout }, "Log out" ) 
                    ) 
                  ) 
                )
            )
          );
};

///////////////// FETCH ////////////////////
/*window.allFetch = async function (endpoint, body = {},method = "POST",headers = { "Content-Type": "application/x-www-form-urlencoded", }) {
  try {
    const res = await fetch(endpoint, { method: method, headers: headers, body: JSON.stringify(body), }); 
    //JSON.stringify(data)....new URLSearchParams(body).toString()
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error("Not valid JSON:", text);
      return { status: "Err500", message: e?.message || "Invalid server response" };
    }
    return data;
  } catch (err) {
    console.error("API error:", err);
    return { status: "failed", message: "Network error" };
  }
};*/

window.squadOpened = false;
window.SquadPay = (email,amt,txrefno)=>{  
      return new Promise((resolve, reject) => {
        if(!txrefno || !amt || !email) {reject("Missing params"); return; }
        if (squadOpened){reject("Already opened"); return; }
        squadOpened = true;

        try{
          const squadInstance = new squad({
              key: SquadPKey,
              email: email,
              amount: Math.round(amt * 100),
              currency_code: "NGN",
              transaction_ref: txrefno,
              payment_channels: ['transfer','card','ussd'], //'bank' , 'ussd', 
              onClose: () => { setTimeout(() => { squadOpened = false; }, 500);  reject("closed");},
              onSuccess: (r) => {
                setTimeout(() => { squadOpened = false; }, 500); 
                resolve(r.transaction_ref);
              },            
          });
          squadInstance.setup();
          squadInstance.open();
        } catch (err) {
          squadOpened = false;
          reject(err);
        }
      });
};

window.apiFetch = async function (endpoint, body = {}) {
  try {
    const res = await fetch(window?.apiEndPoint + endpoint, { method: "POST", credentials: "include", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: new URLSearchParams(body).toString() });
    const text = await res.text();
    let data; // console.log("fetch: "+text);
    try {
      data = JSON.parse(text);
    } catch (e) {
      return { status: "Err500", message: e.message || "Invalid server response" };
    }

    // global auth fail
    //if (data?.status === "LE01") { window.location.href = baseUrl + "/index.html"; return; }
    return data;
  } catch (err) {
    console.error("API error:", err);
    return { status: "failed", message: "Network error" };
  }
};


/////////////////////////////////
window.GetTxSigns = (ty,d,status="")=>{
  let c = null;
  if(ty === "icon"){
    c = ["credit","deposit"].includes(d) ? "arrow-down-right" : "arrow-up-right";
    c = ["pending"].includes(status) ? "clock" : ["failed"].includes(d) ? "x-circle" : c;
    return c;
  }
  if(ty === "bg"){
    return ["credit","deposit"].includes(d) ? "bg-green-100" : "bg-red-100";
  }
  if(ty === "color"){
    c = ["credit","deposit"].includes(d) ? "text-green-600" : "text-red-600"; 
    //c = (["failed"].includes(status)) ? "text-red-600" : (["pending"].includes(status)) ? "text-gray-900":c;
    return c;
  }
  if(ty === "sign"){
    c = ["credit","deposit"].includes(d) ? "+" : "-";
    c = (["pending","failed"].includes(status)) ? "" : c;
    return c;
  }
  if(ty === "num"){
    c = ["credit","deposit"].includes(d) ? "text-green-600" : "text-red-600";
    c = (["failed"].includes(status)) ? "text-red-600 line-through" : (["pending"].includes(status)) ? "text-gray-600":c;
    return c;
  }
  return "";
}
///////////
window.formatCurrency = (amount = 0,useDecimal=false) => {
  const num = Number(amount) || 0;
  return "₦" + num.toLocaleString("en-NG", {
    minimumFractionDigits: useDecimal ? 2 : 0,
    maximumFractionDigits: useDecimal ? 2 : 0
  });
};
///////////////////
window.isValidEmail = (email) => /\S+@\S+\.\S+/.test(email);
/////////////////////
function isPlainObject(obj) {
    return obj !== null && typeof obj === "object" && !Array.isArray(obj);
};
/////////////////
window.saveStorage = (key, value, options = {})=>{
  //options:- merge: true|false (default: auto)
    try {
      const { merge = undefined } = options;
      const existing = getStorage(key);
      let finalValue;

      const canMerge = merge === true || (merge === undefined && isPlainObject(existing) && isPlainObject(value));
      if (canMerge) { finalValue = { ...existing, ...value }; } else { finalValue = value; /*overwrite*/ }
      localStorage.setItem(key, JSON.stringify(finalValue));
    } catch (e) {
      console.error("Storage save failed", e);
    }
};
//////////////////////
window.getStorage = (key, defaultValue = null)=>{
    try {
      const raw = localStorage.getItem(key);
      return raw === null ? defaultValue : JSON.parse(raw);
    } catch (e) {
      console.error("Storage read failed", e);
      return defaultValue;
    }
};
/////////////////////
window.removeStorage = (key)=>{
    localStorage.removeItem(key);
};
////////////////////
window.Convert2Percent = (type="pt2per",part, total,Per5Point) => { //Per5Point(x% per 5pts) 5pts = Per5point...part (pts) = x%
    if(total === 0) return 0;
    if(type === "pt2perbymax") return Number(((part / total) * 100).toFixed(2)); //Convert Usage Point
    if(type === "pt2per") return Number(((Per5Point/5)*part).toFixed(2)); //convert Community Trust Point
};

window.NormUScore = (rawScore,t) => {
    let num = 0;
    if(t === "loan"){
        num = Math.log10(rawScore + 1) * 100;
    } else if(t === "contest"){
        num = Math.log10(rawScore + 1);
    }
    return num;
};

//Calculate contest score for a user.........

window.calculateContestScore = (rawUsageScore=0,repaymentScore=0, weightUsage = 0.7, weightRepayment = 0.3, normalizeMethod = "log")=>{
  let usageNormalized;
  rawUsageScore = Math.max(0, Number(rawUsageScore) || 0);
  repaymentScore = Number(repaymentScore) || 0;

  switch (normalizeMethod) {
    case "log":
      usageNormalized = Math.log10(rawUsageScore + 1); // smooth growth
      break;
    case "sqrt":
      usageNormalized = Math.sqrt(rawUsageScore); // alternative smoother growth
      break;
    case "none":
    default:
      usageNormalized = rawUsageScore; // raw, unbounded
  }

  return usageNormalized * weightUsage + repaymentScore * weightRepayment;
};


//Check Repayment Score. if R < 0, return false
window.CheckCreditForLoan = (vscore,uscore,rscore,cscore) => {
    if(vscore < 40) return false; //min KYC verification score is required
    if(rscore < 0) return false; //Bad repayment score, try apply for loan next time. You can increase your community trust score to rectify this.

    const totalScore = (vscore + rscore + cscore) + uscore;
    if(totalScore < 100) return false;
    return true;
};

/*
// User A: moderate usage, good repayment
let userA = calculateContestScore(1200, 40);
console.log(userA); // ~2.1 + 12 = 14.1
ScoresData = {U:2,V:40,R:35,C:15};
*/
window.GetTotalScores = (rawScores,Per5Point=0)=>{
  let CTP2Percent = 0, UP2Percent = 0;
  if(Per5Point > 0){
      CTP2Percent = Convert2Percent("pt2per",rawScores?.C || 0,5,Per5Point);
      UP2Percent = Convert2Percent("pt2per",rawScores?.UALL || 0,5,Per5Point);
  }  
  const totalCreditScore = (rawScores?.V || 0) + (rawScores?.UALL || 0);
  return {totalCreditScore:totalCreditScore,UP2Percent:UP2Percent,CTP2Percent:CTP2Percent};
};
//////////////////////
window.GetGreeting = (name = "")=>{
  const h = new Date().getHours(); 
  const firstName = name ? name.split(" ")[0] : "";

  if (h < 12) return `Good Morning ${firstName}`;
  else if (h < 18) return `Good Afternoon ${firstName}`;
  else return `Good Evening ${firstName}`;
};
////////////////////////
window.SpinnerOverlay = ({ show })=>{
    if (!show) return null;
    return e("div", {className:"fixed inset-0 z-[100] flex items-center justify-center bg-white/30 backdrop-blur-sm"},
      e("div", {className: "h-12 w-12 rounded-full border-4 border-yellow-400 border-t-transparent animate-spin" })
    );
};
////////////////////////
window.GlobalAlert = ()=>{
    const [alert, setAlert] = useState({ show: false, message: "", type: "info" });
    
    useEffect(() => {
      setGlobalAlert = setAlert;
      lucide.createIcons();
    }, [alert]);

    if (!alert.show) return null;
    
    const colors = {
      info: "bg-white text-black-800",
      success: "bg-green-100 text-green-800 border-green-300",
      error: "bg-red-100 text-red-800 border-red-300",
      warning: "bg-yellow-100 text-yellow-800 border-yellow-300",
    };
  
    return e("div", null, e("div", {key: "backdrop", className: "fixed inset-0 bg-black/50 z-40" }),
              e("div", { key: "alert", className: `fixed top-[30%] left-20 right-20 z-[300] rounded-lg p-5 min-h-[20vh] max-h-[50vh] overflow-y-auto transform transition-transform duration-300 ease-out ${colors[alert.type]}` },
                e("button", {key: "close", onClick: () => setAlert(p => ({ ...p, show: false })), className: "absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition" }, e("i", { "data-lucide": "x-circle", className: "w-6 h-6" }) ), e("div", { key: "handle",  className: "w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4" }),
                e("div",{className:"text-sm"},`${alert.message}`),
              )
    );
};
//////////////////////
window.showAlert = function(message, type = "info") {
  if (!setGlobalAlert) return;
  setGlobalAlert({show: true,message, type });
};
/////////////////////
window.hideAlert = function() {
  if (!setGlobalAlert) return;
  setGlobalAlert(prev => ({ ...prev, show: false }));
};

window.ShortName = (str, isSize = true) => {
    if (isSize) {
      const m = str.match(/(\d+(?:\.\d+)?)\s*(MB|GB|TB)/i);
      return m ? `${m[1]}${m[2].toUpperCase()}` : str;
    } else {
      const m = str.match(/\b(MTN DATA|AIRTIME DATA|GLO DATA|9MOBILE DATA|AIRTIME|UTILITY|CABLE|TAX)\b/i);
      if (!m) return str;
      if (/DATA/i.test(m[1])) {
        const network = m[1].split(" ")[0];
        return `${network.toUpperCase()} Data`;
      }
      return m[1].toUpperCase();
    }
};

window.toCapitalCase = (str)=>{
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

window.UCaseNetworkOnly = (str) => {
  if (!str) return "";
  const networks = ["MTN", "AIRTEL", "GLO", "9MOBILE"];

  return str.split(" ").map(word => {
      const upper = word.toUpperCase();
      if (networks.includes(upper)) {
        return upper; // keep network uppercase
      }
      return toCapitalCase(word);
    }).join(" ");
};

window.PlanType = (n)=>{
    return ((n == 1) ? "Daily Plan" : (n > 1 && n < 7 ? n+" Days Plan":(n >= 7 && n < 30 ? "Weekly Plan":"Monthly Plan")));
};
///////////////
window.Deposit = ()=>{ ///pop up
  const amountToPay = 5000; // Naira...
};
///////////////////////
Object.freeze(flwNames);
Object.freeze(flwIcons);
Object.freeze(flwColors);
