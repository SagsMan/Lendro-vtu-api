const FooterNav = ({onMenuClick,page,setPage}) => { 
  return e("div",{className: "fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-3 flex justify-between items-center z-30 shadow-[0_-4px_12px_rgba(0,0,0,0.1)]" },
            /* Home */
            e("div",{ className: `flex flex-col items-center gap-1 text-${page==="home"?"[#4F46E5]":"gray-500"} cursor-pointer`,onClick:()=>setPage("home") },
                e("i", { "data-lucide": "home", className: "w-6 h-6" }),
                e("span", { className: "text-xs font-semibold" }, "Home")
            ),

            /* Loan */
            e("div", { className: `flex flex-col items-center gap-1 text-${page==="loan"?"[#4F46E5]":"gray-500"} cursor-pointer`,onClick:()=>setPage("loan") },
                e("i", { "data-lucide": "file-text", className: "w-6 h-6" }),
                e("span", { className: "text-xs font-semibold" }, "Funding") //Get Funds
            ),

            /* Purchase (Center) */
            e("div",{ className: "flex flex-col items-center -mt-8 cursor-pointer",onClick:()=>setPage("services") },
                e("div", {className: "w-14 h-14 bg-[#4F46E5] border-2 border-[#ffffff] rounded-full flex items-center justify-center"},
                e("i", {"data-lucide": "shopping-cart", className: "w-7 h-7 text-white" })
                ),
                e( "span", { className: `text-xs font-semibold text-${page==="services"?"[#4F46E5]":"gray-500"}` },"Purchase" ) //  mt-1 font-semibold text-[#4F46E5]
            ),

            /* Contest */
            e( "div", { className: `flex flex-col items-center gap-1 text-${page==="contest"?"[#4F46E5]":"gray-500"} cursor-pointer`,onClick:()=>setPage("contest") },
                e("i", { "data-lucide": "trophy", className: "w-6 h-6" }),
                e("span", { className: "text-xs font-semibold" }, "Grants")
            ),

            /* Menu */
            e("div",  { className: "flex flex-col items-center gap-1 text-gray-500 cursor-pointer", onClick: (e) => { e.stopPropagation(); onMenuClick(); } },
                e("i", { "data-lucide": "menu", className: "w-6 h-6" }),
                e("span", { className: "text-xs font-semibold" }, "Menu")
            )
        );
};

//export default FooterNav;
