const MyScores = ({wallet,setPage,maxCUPoint,Per5Point}) => {

  const scores = wallet?.scores || {};
  const kycPoint = scores?.V || 0;
  const usagePoint = scores?.UALL || 0;
  const repayScore = scores?.R || 0;
  const totalscore = (kycPoint+usagePoint);

  const target = ExpectedTotalCS;
  const remaining = Math.max(0, target - usagePoint);
  const progress = Math.min(100, (usagePoint / target) * 100);

  const totalmaxUPercent =  Convert2Percent("pt2perbymax",scores, maxCUPoint,Per5Point) || 0;

  const Progress = ({title,value,valper=null,max=200,maxlabel})=>{
    const percent = (valper) ? valper : Math.min(100,(value/max)*100);

    return e("div",{className:"space-y-1"},
      e("div",{className:"flex justify-between text-sm"},
        e("span",{className:"font-medium text-gray-800"},title),
        e("span",{className:"text-gray-500"},`${maxlabel}`)
      ),
      e("div",{className:"w-full h-2 bg-gray-200 rounded-full overflow-hidden"},
        e("div",{className:"h-full bg-indigo-600", style:{width:`${percent}%`}})
      ),
      //(valper) && e("p", { className: "text-xs text-gray-500" }, `${totalmaxUPercent+"%"} relative to community peak.`)
    );
  };

  return e("div",{className:"px-3 py-5 space-y-5"},

    /* HEADER */
    e("div",{className:"flex items-center justify-between"},
      e("h2",{className:"text-xl font-bold text-gray-900"},"My Scores & Benefits"),
      e("i",{"data-lucide":"award",className:"w-6 h-6 text-indigo-600"})
    ),

    /* TOTAL POINTS CARD */
    e("div",{className:"bg-gradient-to-br from-indigo-600 to-indigo-500 text-white rounded-2xl p-4 pb-5 shadow-lg"},
      e("p",{className:"text-sm opacity-80"},"Total Points Earned"),
      e("h3",{className:"text-3xl font-bold mt-1"},totalscore),

      e("div",{className:"mt-3 text-sm opacity-90 space-y-1"},
        e("div",{className:"flex justify-between"},
          e("span",{className:"font-medium text-indigo-100"},"Progress to RSF"),
          e("span",{className:"text-yellow-400 opacity-100 font-semibold"},`${usagePoint}/200`)
        ),
        e("div",{className:"w-full h-3 bg-gray-200 rounded-full overflow-hidden"},
          e("div",{className:"h-full bg-green-500", style:{width:`${progress}%`}})
        ),
        e("div",{className:"text-xs"}, remaining > 0 ? `${remaining} points left to unlock Repayable Support Fund` : "Support Fund Access Unlocked")
      )
    ),

    /* SCORES BREAKDOWN */
    e("div",{className:"bg-white rounded-2xl p-4 shadow space-y-4"},

      e("h3",{className:"font-semibold text-gray-900 flex items-center gap-2"},
        e("i",{ "data-lucide":"bar-chart-3", className:"w-5 h-5 text-indigo-600"}),
        "Score Breakdown"
      ),

      Progress({title:"KYC & Participation Points", value:kycPoint, max:80,maxlabel:`${kycPoint}/80`}),
      Progress({title:"Usage Points", value:usagePoint, valper:totalmaxUPercent, max:200,maxlabel:`${usagePoint}/∞`}),
      Progress({title:"Repayment Score", value:repayScore, max:100,maxlabel:`${repayScore}%`})
    ),

    /* BENEFITS CARD */
    e("div",{className:"bg-gray-50 rounded-2xl p-4 text-sm text-gray-700 flex gap-2"},
      e("i",{ "data-lucide":"info", className:"w-5 h-5 text-indigo-500 mt-[2px]"}),
      e("div",null,
        e("p",{className:"font-semibold text-gray-900"},"Unlock Community Support Fund"),
        e("p",null,
          "When your Total Points reach 200, you gain access to the interest-free Repayable Support Fund (RSF). Grow your yearly usage points to qualify for the Top-20 Annual Community Reward Grant. This program runs every year, starting from Jan 1 to Dec 20."
        )
      )
    ),

    /* CALL TO ACTION */
    e("button",{
      className:"w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3 rounded-xl font-semibold transition",
      onClick:()=>setPage("services")
    },
      "Use Partner Services to Earn Points"
    )
  );
};


//const ScoresData = {U:2,V:40,R:35,C:15};
const ScoreBreakdown = ({scores,maxCUPoint,Per5Point}) => {
  const rawUScore = scores?.UALL || 0;
  const totalmaxUPercent =  Convert2Percent("pt2perbymax",rawUScore, maxCUPoint,Per5Point) || 0;
  const VScore = scores?.V || 0;
  const KYC3Per = (VScore / MaxKYCPoint)*100;


  return e("div", { className: "bg-white rounded-2xl p-5 shadow shadow-gray-300/50 space-y-4" },

    e("h3",{className:"font-semibold text-gray-900 flex items-center gap-2"},
      e("i",{ "data-lucide":"bar-chart-3", className:"w-5 h-5 text-indigo-600"}),"Score Breakdown"
    ),

    // V% Score Item
    e("div", { className: "space-y-1" }, 
        e("div", { className: "flex justify-between text-sm" },
          e("span", {className: "font-medium text-gray-800" }, "Participation Points"),
          e("span", {className: "text-gray-500" }, (VScore || 0))
        ),
        e("div", { className: "w-full h-2 bg-gray-200 rounded-full overflow-hidden" },
          e("div", { className: `h-full bg-indigo-600`, style: { width: (KYC3Per.toFixed(2) || 0) + "%" } })
        ),
    ),

    // U Score/Points Item
    e("div", { className: "space-y-1" }, 
        e("div", { className: "flex justify-between text-sm" },
          e("span", {className: "font-medium text-gray-800" }, "Usage Points"),
          e("span", {className: "text-gray-500" }, (rawUScore || 0) + "/∞")
        ),
        e("div", { className: "w-full h-2 bg-gray-200 rounded-full overflow-hidden" },
          e("div", { className: `h-full bg-indigo-600`, style: { width: totalmaxUPercent + "%" } })
        ), 
        e("p", { className: "text-xs text-gray-500" }, `${totalmaxUPercent+"%"} relative to community peak.`)
     ),
    
    // R% Score Item
    e("div", { className: "space-y-1" }, 
        e("div", { className: "flex justify-between text-sm" },
          e("span", {className: "font-medium text-gray-800" }, "Repayment Score (±)"),
          e("span", {className: "text-gray-500" }, (scores?.R || 0) + "%")
        ),
        e("div", { className: "w-full h-2 bg-gray-200 rounded-full overflow-hidden" },
          e("div", { className: `h-full bg-indigo-600`, style: { width: (scores?.R || 0) + "%" } })
        ), 
    ),

  );
};

// Calculate total score (sum of percentages)
const calculateTotalScore = (scores) => {
  return Object.values(scores).reduce((total, num) => total + num, 0);
};

// To Add 5% to Usage Score or any later, use the function below....addScore("U", 5);
const addScore = (scoreKey, increment) => {
  //setScores(prevScores => ({ ...prevScores, [scoreKey]: prevScores[scoreKey] + increment }));
  setScores(prevScores => ({ ...prevScores, [scoreKey]: Math.min(prevScores[scoreKey] + increment, 100) }));
};