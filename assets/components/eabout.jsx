const About = ({setPage}) => {

  const sectionTitle = (text) =>
    e("h3",{className:"text-indigo-600 font-bold text-lg mb-2"}, text);
  
  const card = (...children) =>
    e("div",{className:"bg-white rounded-2xl shadow-lg shadow-gray-200/60 p-5 space-y-3"}, ...children);

  const highlight = (text) =>
    e("div",{className:"bg-yellow-100 border border-yellow-200 rounded-xl p-3 text-sm text-gray-800"}, text);

  return e("div", { className: "app-page pb-6" },

    e(innerHeader,{pgtitle:"About Lendro",setPage}),

    e("div", { className: "px-3 mt-5 space-y-6" },

      /* INTRO */
      card(
        sectionTitle("Introduction"),

        e("p",{className:"text-sm text-gray-700"},
          "Lendro is a community support platform designed to empower individuals and businesses through everyday transactions."
        ),

        e("p",{className:"text-sm text-gray-700"},
          "By using Lendro to buy airtime, data, pay bills, and access partner services, users earn points that unlock real financial opportunities."
        ),

        e("p",{className:"text-sm font-semibold text-gray-900"},
          "At its core, Lendro is built on one simple idea:"
        ),

        highlight(
          "As you patronize our partner services, you earn rewards and gain access to financial opportunities that uplift you and your community."
        )
      ),

      /* MEMBERSHIP */
      card(
        sectionTitle("Membership Types"),

        e("p",{className:"text-sm text-gray-700"},
          "We offer two main membership categories: Basic and Premium (Bronze, Platinum, and Diamond). All members enjoy core platform benefits, while the oShare Program is exclusive to Premium members."
        ),

        highlight(
          "Premium members have a higher chance of being approved for support funding and also enjoy increased funding limits."
        )
      ),

      /* BENEFITS */
      card(
        sectionTitle("Member Benefits"),

        e("div",{className:"space-y-3 text-sm text-gray-700"},

          e("div",null,
            e("p",{className:"font-bold text-gray-900"},"Support Funding"),
            e("p",null,"Access repayable funds for personal or business needs at zero interest, with only a small administrative fee.")
          ),

          e("div",null,
            e("p",{className:"font-bold text-gray-900"},"Grants"),
            e("p",null,"Various grant opportunities are provided to support and improve members’ lives and businesses.")
          ),

          e("div",null,
            e("p",{className:"font-bold text-gray-900"},"oShare Rewards"),
            e("p",null,"A percentage of partner contributions is shared monthly among Premium members as continuous rewards.")
          )
        )
      ),

      /* OSHARE */
      card(
        sectionTitle("oShare Program"),

        e("p",{className:"text-sm text-gray-700"},
          "The oShare Program is an exclusive reward system for Premium members, offering continuous monthly earnings from the oShare Pool."
        ),

        e("p",{className:"text-sm text-gray-700"},
          "A percentage of Lendro’s net profit is allocated to this pool and distributed among members based on their plan and activity."
        ),

        //sectionSubTitle("How to Participate"),

        
        e("div",null,
            e("p",{className:"font-bold text-sm text-gray-900"},"How to Participate"),
            e("ul",{className:"list-disc ms-5 text-sm text-gray-700 space-y-1"},
              e("li",null,"Choose a premium plan"),
              e("li",null,"Actively use partner services monthly")
            ),
        ),

        e("div",null,
            e("p",{className:"font-bold text-sm text-gray-900"},"oShare Pool Example"),
            e("p",{className:"text-sm text-gray-700"},
              "If Lendro generates ₦5,000,000 in profit and 25% is allocated, ₦1,250,000 becomes available for distribution."
            ),
        ),


        highlight(
          "This pool is shared among Premium members based on their activity and plan weight, ensuring fair and performance-based rewards."
        ),

        e("p",{className:"text-sm text-gray-700"},
          "As Lendro grows, the oShare Pool increases — meaning your earning potential also grows."
        ),

        e("p",{className:"text-sm font-semibold text-gray-900"},
          "There is no earning limit. Stay active and keep earning."
        )
      )
    )
  );
};