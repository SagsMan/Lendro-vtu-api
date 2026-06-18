const Notifications = ({setPage}) => {

  const NoteBox = ({title,desc,price,setPage}) => { return e( "div", { className: "bg-white rounded-3xl p-5 shadow-lg flex flex-col items-center gap-4" }, 
        e( "h3", { className: "text-indigo-100 font-bold text-3xl" }, `${title}` ), 
        e( "p", { className: "text-md leading-relaxed" }, `${desc} - Registration Fee: ${formatCurrency(price,true)}`, 
          e( "span", { className: "flex items-center justify-center gap-3 mt-3" }, 
            e( "span", { className: "text-yellow-400 font-semibold cursor-pointer hover:underline",onClick:()=>setPage("join") }, "Verify" )
          ) 
        ) 
      ); 
  }; 

  return e("div", { className: "app-page pb-5" },
    e(innerHeader,{pgtitle:"About Us",setPage}),
    e("div", { className: "app-content px-3 pb-5 mt-5 mb-5 space-y-6" },
        e("h3",{className:"text-lg mb-2"}, `Introduction`), 
        e("p",{className:"text-sm mb-2"}, `Lendro is a non-commercial community support foundation and a subsidiary of Progmatech Solutions. Its mission is to empower members through structured programs including Reward Grants, Repayment Support Fund (RSF), and WealthCircle Growth Funds.`), 
        e("p",{className:"text-sm mb-2"}, `Our programs are supported by partner organizations that contribute to community development when members use their services.`), 
        e("p",{className:"text-sm mb-2"}, `Revenue generated through these services enables our partners to continue funding and sustaining community initiatives.`), 

        /*e("div", { className: "mt-5 mb-5" },
            e("h3",{className:"text-lg mb-2"}, `Membership Accounts`), 
            e("p",{className:"text-sm mb-2"}, `Lendro allows members to choose from different account below to unlock benefits from the foundation.`), 
            e(NoteBox,{title: "Community Member", desc:"Verify Account, Access partner services, earn usage points, and qualify for Repayable Support Fund (RSF) and Grant Programs.",price:"100",setPage}),
        )*/
        
    ),
    
  );

};