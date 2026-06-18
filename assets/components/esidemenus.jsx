const SideMenus = ({ name,phone,menuOpen,onMenuClick,handleLogout,setPage }) => {
//console.log(baseUrl+"/assets/images/noimage.png");
  return  e("div",{id:"sideMenu",className: "fixed inset-0 z-40 transition-all duration-300 " + (menuOpen ? "visible":"invisible")},
            /* Backdrop */
            e("div", {className: "absolute inset-0 bg-black/40 transition-opacity duration-300 "+(menuOpen ? "opacity-100" : "opacity-0"), onClick: (e) => {e.stopPropagation(); onMenuClick();} }),

            /* Side Menu */
            e("div", { className:"absolute top-0 right-0 rounded-tl-3xl rounded-bl-3xl h-full w-72 bg-white shadow-2xl p-5 transform transition-transform duration-300 ease-out " + (menuOpen ? "translate-x-0" : "translate-x-full"), onClick: (e) => e.stopPropagation() },
                e( "div", { className: "flex items-center justify-between gap-2 mb-5" },
                        e("img",{src: `${baseUrl}/assets/images/noimage.png`, className: "w-8 h-8 rounded-full object-cover"}),
                        e("div",{className: "w-1/2 text-end"},
                                e("p", { className: "text-sm font-bold text-[#4F46E5]" }, `${name}` ),
                                e("div",{ className:"flex items-center gap-2 justify-end" },
                                        e("i", { "data-lucide": "phone", className: "w-4 h-4" }),
                                        e("p", { className:"text-sm" }, phone)
                                ) 
                        )
                ),
                
                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("services");onMenuClick();} },
                        e("i", { "data-lucide": "shopping-cart", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Buy Services")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("loan"); onMenuClick();}},
                        e("i", { "data-lucide": "file-text", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Get Funds (RSF)")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("wcfund"); onMenuClick();}},
                        e("i", { "data-lucide": "users", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "oShare Reward")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("contest"); onMenuClick();}},
                        e("i", { "data-lucide": "trophy", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Grants")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("wallet"); onMenuClick();}},
                        e("i", { "data-lucide": "wallet", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "My Wallet")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("myscores"); onMenuClick();}},
                        e("i", { "data-lucide": "bar-chart-3", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "My Scores & Benefits")
                ),
                
                //Community-powered civic intelligence system
                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("voices"); onMenuClick();}},
                        e("i", { "data-lucide": "megaphone", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Community Voice")
                ),
                /*e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer"},
                        e("i", { "data-lucide": "repeat", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Transactions")
                ),
                */              

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("verify"); onMenuClick();}},
                        e("i", { "data-lucide": "user-check", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "KYC & Bank Account")
                ),
                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("profile"); onMenuClick();}},
                        e("i", { "data-lucide": "user-cog", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Personal Settings")
                ),
                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: ()=>{setPage("help"); onMenuClick();}},
                        e("i", { "data-lucide": "help-circle", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Help Center")
                ),

                e("div",{className: "flex items-center gap-3 p-3 rounded-xl border-t border-black/10 hover:bg-gray-100 cursor-pointer", onClick: handleLogout},
                        e("i", { "data-lucide": "log-out", className: "w-5 h-5 text-[#4F46E5]" }),
                        e("span", { className: "font-medium text-gray-800" }, "Logout" )
                ),
                
            )
        );
};
//export default SideMenus;