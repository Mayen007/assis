

<!-- Start of picture text -->
Mount Kenya University<br><!-- End of picture text -->

SCHOOL OF COMPUTING AND INFORMATICS DEPARTMENT OF INFORMATION TECHNOLOGY 

**AFTER-SALES SERVICE INFORMATION SYSTEM** 

## **Name: NAJMA HASSAN** 

## **ADM: BIT/2024/48669** 

BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY 

30<sup>th</sup> July 2026 

## Table of Contents 

<!-- |CHAPTER ONE: INTRODUCTION........................................................................................4<br>1.0 Background......................................................................................................................4|
|---|
|1.1 Problem Statement...........................................................................................................4|
|1.2 Objectives.........................................................................................................................5|
|1.2.1 General Objective......................................................................................................5|
|1.2.2 Specific Objectives....................................................................................................5|
|1.3 Scope................................................................................................................................6|
|1.4 Justification......................................................................................................................6|
|1.5 Potential Risks..................................................................................................................7|
|1.6 Budget.............................................................................................................................. 8|
|1.7 Project Schedule...............................................................................................................9|
|CHAPTER TWO: LITERATURE REVIEW...........................................................................10|
|2.0 Introduction....................................................................................................................10|
|2.1 After-Sales Service Information Systems......................................................................10|
|2.2 Customer Relationship Management and Service Management Systems.....................11|
|2.3 Spare Parts and Inventory Management Systems..........................................................11|
|2.4 Review of Related Systems............................................................................................12|
|2.5 Research Gap.................................................................................................................13|
|2.6 Conceptual Framework..................................................................................................13|
|CHAPTER THREE: METHODOLOGY.................................................................................15|
|3.0 Introduction....................................................................................................................15|
|3.1 System Development Model..........................................................................................15|
|3.2 Requirements Gathering.................................................................................................16|
|3.2.1 Interviews................................................................................................................16|
|3.2.2 Observation.............................................................................................................17|
|3.2.3 Document Review...................................................................................................17|
|3.3 Analysis of the Current System......................................................................................17|
|3.4 Limitations of the Current System.................................................................................18|
|3.5 Proposed System............................................................................................................ 18|
|3.6 Expected Benefits of the Proposed System....................................................................19|
|CHAPTER FOUR: SYSTEM ANALYSIS..............................................................................20|
|4.0 Introduction....................................................................................................................20|



ii 

|4.1 System Requirements.....................................................................................................20|
|---|
|4.1.1 Functional Requirements........................................................................................20|
|4.1.2 Non-Functional Requirements................................................................................21|
|4.2 Data Flow Diagrams......................................................................................................22|
|4.2.1 Context Diagram.....................................................................................................22|
|4.2.2 Level One Data Flow Diagram...............................................................................23|
|4.3 Entity Relationship Diagram (ERD)..............................................................................24|
|CHAPTER FIVE: SYSTEM DESIGN....................................................................................26|
|5.0 Introduction....................................................................................................................26|
|5.1 System Architecture.......................................................................................................26|
|Presentation Layer............................................................................................................27|
|Application Layer.............................................................................................................27|
|Data Layer........................................................................................................................27|
|5.1.1 Proposed Development Tools..................................................................................27|
|5.2 Database Design.............................................................................................................28|
|5.2.1 Database Requirements Analysis............................................................................28|
|5.2.2 Logical Database Design.........................................................................................29|
|5.2.3 Physical Database Design.......................................................................................29|
|5.3 Input Design...................................................................................................................32|
|5.4 Output Design................................................................................................................34|
|5.5 Summary........................................................................................................................35|
|REFERENCES.........................................................................................................................37| -->



iii 

# **CHAPTER ONE: INTRODUCTION** 

## **1.0 Background** 

Customer service does not terminate at the point of sale. In enterprises that supply tangible goods—such as electronic appliances, industrial machinery, vehicles, and similar products— the durability of customer relations is frequently determined post-sale when items require maintenance, repair, or replacement components. This post-sale phase, commonly referred to as after-sales service, encompasses warranty repairs, technical assistance, spare-parts provision, and the management of customer feedback. 

In numerous small and medium-sized service enterprises, after-sales operations are administered through manual mechanisms including ledger books, disparate spreadsheets, telephonic communications, and informal follow-ups. While such approaches may have sufficed when request volumes were limited, they become inadequate as demand increases. Consequences include loss of job tracking, inadequate customer communication, unanticipated depletion of spare parts, and absence of managerial visibility into service performance metrics. 

This study proposes the design and implementation of an After-Sales Service Information System (ASSIS), a centralized, database-driven application intended to supplant manual procedures. The proposed system will enable systematic registration of customer complaints, allocation of technicians, monitoring of repair status, management of spare-parts inventory, generation of invoices, and systematic capture of customer feedback. The objective is to enhance responsiveness and service quality through structured information management rather than merely digitizing existing paperwork. 

## **1.1 Problem Statement** 

The current after-sales service process is predominantly manual, a situation that has engendered operational inefficiencies and information deficits detrimental to both the enterprise and its clientele. Service requests are recorded as handwritten entries in a logbook and are therefore susceptible to misplacement and oversight. Technician assignments are communicated verbally or telephonically, resulting in scheduling conflicts and an absence of shared workload visibility. Customer enquiries concerning repair status require staff to 

4 

consult physical records, often producing indeterminate responses. Spare-part inventory is maintained manually, precipitating stock outs that delay repairs. Invoicing is performed by hand, increasing the probability of errors and complicating financial reconciliation. 

Crucially, management lacks reliable, consolidated reports on key performance indicators such as technician productivity, frequency of repeat repairs by product, mean resolution time, and customer satisfaction levels. Consequently, managerial decisions are frequently made without empirical support. The foregoing limitations substantiate the need for a dedicated After-Sales Service Information System that centralizes service data, automates routine record-keeping, and provides management with actionable insights. 

## **1.2 Objectives** 

### **1.2.1 General Objective** 

To design and implement a computerized After-Sales Service Information System that streamlines the registration, tracking, and management of customer service requests, technician assignments, spare-parts inventory, billing, and customer feedback. 

### **1.2.2 Specific Objectives** 

1. Investigate and document the existing manual after-sales service processes, including operational practices, strengths, weaknesses, and user requirements. 

2. Design a relational database schema and system architecture (including an Entity– Relationship Diagram) to represent customers, products, technicians, service requests, spare parts, invoices, and feedback. 

3. Develop and implement core system modules for customer and service-request management, technician assignment and scheduling, status tracking, and invoicing. 

4. Implement an inventory management module that monitors spare-parts stock levels, records parts consumption, and generates automated low-stock alerts. 

5. Evaluate the usability and adequacy of the proposed system design with a sample of representative end users prior to development. 

5 

## **1.3 Scope** 

This study encompasses the analysis and **design** of an After-Sales Service Information System intended for deployment within a single service enterprise operating one or multiple physical service centres. The system shall include: 

- Customer registration and product linkage to individual customers. 

- Logging and tracking of service requests from initiation to closure. 

- Technician assignment, specialisation mapping, and workload visibility. 

- Spare-parts inventory tracking with stock deduction on parts usage and low-stock notification. 

- Invoice generation for service events and basic payment-status tracking. 

- Customer feedback capture for ratings and textual comments. 

- Management reporting functionality summarising request status, technician performance, and parts usage over selectable periods. 

Excluded from the present scope are: the **development, implementation, and testing of the system** , which fall outside this proposal and are identified as the subject of subsequent work; the development of a public customer self-service portal; mobile application development; integration with external payment gateways; comprehensive procurement or supplier management beyond basic stock tracking; and full financial accounting functionality outside service-related invoicing. These exclusions are intentional to maintain feasibility within the time and resources available for this proposal. 

## **1.4 Justification** 

The proposed system is justified on operational, managerial, and pedagogical grounds. Operationally, centralised record-keeping and automation will reduce time losses associated with locating records, assigning technicians, and preparing invoices, thereby decreasing service turnaround times and enhancing customer experience. Managerially, the system will provide verifiable accountability via timestamped records and user attributions, facilitating the identification and remediation of bottlenecks. Additionally, access to consolidated reports on service volume, repeat fault rates, technician productivity, and customer satisfaction will enable data-driven decision-making relating to inventory management, staff training, and performance recognition. Academically and practically, the project constitutes an application 

6 

of database design, systems analysis, and software engineering to a real organisational problem and may yield a deployable prototype of value to the host organisation. 

## **1.5 Potential Risks** 

|**Risk**|**Description**|**Mitigation**|
|---|---|---|
|Scope creep|Stakeholders may request<br>additional features during<br>implementation.|Requirement freeze<br>following sign-off; maintain<br>a backlog of change requests<br>for subsequent versions.|
|Data loss|Corruption or loss of code and<br>databases during development.|Employ Git-based version<br>control and routine backups<br>to remote/cloud storage.|
|Incomplete or<br>inaccurate<br>requirement data:|Respondent information may be<br>insufficient or inconsistent.|Triangulate requirements<br>through interviews, direct<br>observation, and<br>documentary review.|
|Skill limitations|Limited familiarity with requisite<br>development frameworks or tools.|Allocate initial time for<br>upskilling, develop proof-<br>of-concept modules, and<br>seek supervisory guidance.|
|Stakeholder<br>unavailability|Delays due to inaccessibility of<br>supervisors or key respondents.|Schedule consultations in<br>advance, maintain written<br>communication logs, and<br>include temporal buffers in<br>the schedule.|
|||Use cloud-synchronised|
|Hardware failure or<br>power interruptions|Loss of primary development<br>workstation|repositories, maintain recent<br>local backups, and identify<br>contingency workstations.|
|User acceptance|Late detection of critical defects.|Adopt iterative testing and|



7 

|testing failure<br>|continuous user feedback|
|---|---|
|proximate to||
|<br>submission|throughout development.|



_Table 1.1 Potential Risks and Mitigation_ 

## **1.6 Budget** 

The table below presents an estimated budget for the design, development, testing, and deployment of the proposed system. The figures are estimates based on prevailing local market rates at the time of writing and should be treated as a planning guide rather than a final costed quotation. 

|**No.**|**Item Description**|**Quantity /**<br>**Duration**|**Unit**<br>**Cost**<br>**(KES)**|**Total Cost**<br>**(KES)**|
|---|---|---|---|---|
|**1**|Laptop/development workstation (existing,<br>allocated)|1|0|0|
|**2**|Internet bundles and data for research and<br>testing|5 months|2,000|10,000|
|**3**|Domain name and web hosting (1 year, for<br>deployment/demo)|1|6,500|6,500|
|**4**|Database/cloud server subscription (testing<br>tier)|5 months|1,500|7,500|
|**5**|Printing, binding, and photocopying of<br>project documents|Lump sum|3,500|3,500|
|**6**|Stationery (notebooks, pens, flash disk)|Lump sum|1,500|1,500|
|**7**|Transport for data collection and<br>consultations|Lump sum|4,000|4,000|
|**8**|Airtime and communication with<br>respondents/supervisor|Lump sum|1,500|1,500|
|**9**|Miscellaneous and contingency (approx.<br>10%)|Lump sum|3,500|3,500|
||**Total Estimated Budget (KES)**|||**38,000**|



_Table 1.2 Estimated Budget_ 

8 

## **1.7 Project Schedule** 

The project is planned to run over approximately twenty-one weeks, from initial topic selection through to final submission. The schedule below was built around the major phases of the System Development Life Cycle and is presented as a Gantt chart to make overlapping and sequential activities easy to visualize. 



<!-- Start of picture text -->
Topic Selection & Proposal Wtng 2 aks<br>Requirements Gathering & Fasibiity Study ; | aks<br>Literature Review | aks<br>System Analysis (ERD, DFD, Use Cases) ; | wks.<br>Database Design || ats<br>User Interface Design a]Lk<br>System Implementation<br>| Coding f avis<br>System Testing & Debugging | 2s<br>User Training & Documentation ~~ Lwk<br>Deployment | LW<br>Final Report Writing& Submission || wks<br>a Pa & e * rs S ° e s<br>Project Timeline (2026)<br><!-- End of picture text -->

_Figure 1.1 Project Schedule (Gantt chart)_ 

9 

# **CHAPTER TWO: LITERATURE REVIEW** 

## **2.0 Introduction** 

This chapter reviews literature relevant to the proposed After-Sales Service Information System. The review examines concepts and studies related to after-sales service, customer relationship management, service management systems, and inventory management. It further reviews existing systems employed in after-sales service management, identifies the research gap addressed by the study, and presents the conceptual framework that guided the design of the proposed system. 

## **2.1 After-Sales Service Information Systems** 

After-sales service refers to the range of support services provided to customers following the purchase of a product. These services include warranty administration, maintenance, repairs, technical support, spare parts provision, and customer feedback management. Effective aftersales service has increasingly become a strategic factor in enhancing customer satisfaction, strengthening customer loyalty, and improving organizational competitiveness. 

(Lovelock & Wirtz, 2016) argue that customer retention is significantly less costly than customer acquisition, making the quality of post-purchase support an important determinant of long-term organizational performance. Similarly, (Kotler & Keller, 2016) contend that customer satisfaction during the post-purchase stage has a substantial influence on repeat purchases and customer referrals. Consequently, organizations that fail to provide efficient after-sales services risk diminished customer confidence and reduced market competitiveness. 

The growing reliance on information systems has transformed the management of after-sales services by replacing manual record-keeping with centralized digital platforms. Such systems facilitate the registration of customer complaints, monitoring of repair activities, management of warranties, tracking of service histories, and generation of management reports. These capabilities contribute to improved operational efficiency, enhanced service quality, and more informed managerial decision-making. 

10 

## **2.2 Customer Relationship Management and Service Management Systems** 

Customer Relationship Management (CRM) systems are designed to manage customer interactions throughout the customer lifecycle, including post-purchase service activities. Modern CRM solutions frequently incorporate service management modules that support complaint handling, technician scheduling, service request monitoring, and customer communication. 

(Kendall & Kendall, 2014) observe that structured information systems replace fragmented manual processes with centralized databases that promote consistency, accessibility, and data integrity across organizational operations. Similarly, (Pressman & Maxim, 2020) emphasize that successful information systems are founded upon comprehensive requirements analysis, which ensures that system functionality aligns with organizational processes and user requirements. 

Commercial service management platforms such as Salesforce Service Cloud, Microsoft Dynamics, Zoho Desk, and Freshdesk provide comprehensive functionality for customer support and field service management. These systems generally include features such as ticket management, technician scheduling, workflow automation, and performance reporting. However, their implementation often requires substantial licensing costs, specialized technical expertise, and extensive configuration, making them less suitable for many small and medium-sized enterprises. 

Consequently, numerous organizations continue to rely on manual procedures for managing after-sales services despite the operational limitations of these approaches. 

## **2.3 Spare Parts and Inventory Management Systems** 

Efficient management of spare parts constitutes a critical component of after-sales service operations. Inventory management systems facilitate the recording of stock movements, monitoring of inventory levels, forecasting of demand, and generation of reorder notifications. 

(Connolly & Begg, 2014) emphasize that properly designed database systems improve inventory accuracy by minimizing data redundancy and ensuring consistency across 

11 

transactions. Likewise, (Silberschatz, Korth, & Sudarshan, 2019) argue that normalized database structures enhance data integrity by reducing duplication and enforcing logical relationships among data entities. 

Within an after-sales service environment, inventory management systems should be integrated with service request processing to ensure that spare parts issued during repairs are automatically deducted from available stock. Such integration minimizes discrepancies associated with manual inventory records while improving inventory visibility and facilitating timely stock replenishment. 

## **2.4 Review of Related Systems** 

Several existing systems were examined to identify common approaches employed in aftersales service management. 

Helpdesk management systems organize customer complaints into service tickets that progress through predefined workflow stages, including Open, Assigned, In Progress, Pending, and Closed. This structured workflow enhances accountability and enables continuous monitoring of service requests throughout their lifecycle. 

Equipment maintenance systems maintain comprehensive service histories for individual assets, thereby enabling technicians to review previous maintenance activities before undertaking subsequent repairs. This approach informed the decision to associate products directly with individual customers rather than maintaining customer records alone. 

Inventory management systems automatically update stock quantities whenever spare parts are issued during repair activities. This functionality improves inventory accuracy, minimizes manual stock reconciliation, and supports effective procurement planning. 

Although these systems address specific aspects of after-sales service management, they generally operate as independent applications. Consequently, organizations often rely on multiple software solutions to manage customer records, repair activities, inventory, and invoicing, resulting in fragmented workflows and duplication of data. 

12 

## **2.5 Research Gap** 

The reviewed literature demonstrates the significance of after-sales service in enhancing customer satisfaction, customer retention, and organizational performance. It further establishes the effectiveness of computerized service management systems in improving operational efficiency and decision-making. However, the majority of available commercial solutions are designed for medium and large enterprises and require considerable financial investment, technical expertise, and organizational resources. 

Furthermore, existing systems frequently address customer management, technician scheduling, inventory management, and invoicing as separate functional components or standalone applications. This separation limits the availability of integrated information required for efficient management of after-sales service operations, particularly within small and medium-sized service organizations. 

The literature also reveals limited documentation concerning information systems specifically designed to support the operational requirements of small and medium-sized service enterprises in developing economies. Accordingly, the proposed After-Sales Service Information System seeks to address this gap by integrating customer management, product registration, service request tracking, technician assignment, spare parts inventory management, invoicing, and customer feedback within a single database-driven application tailored to the operational requirements of the organization under study. 

## **2.6 Conceptual Framework** 

The conceptual framework illustrates the relationship between the proposed After-Sales Service Information System and the quality of after-sales service delivery. 

The independent variable comprises the core functional components of the proposed system, including customer management, service request management, technician assignment, spare parts inventory management, invoicing, and customer feedback management. The dependent variable is after-sales service delivery, measured through indicators such as reduced service turnaround time, improved record accuracy, enhanced technician accountability, increased customer satisfaction, and improved management reporting. 

13 

The relationship between these variables is moderated by factors including staff ICT competency, management support, system usability, and the availability of appropriate technological infrastructure. These factors influence the extent to which the implementation of the proposed system contributes to improvements in service delivery. 



<!-- Start of picture text -->
INDEPENDENT VARIABLE DEPENDENT VARIABLE<br>After-Sales Service Information System Quality of After-Sales Service Delivery<br>+ Service Request Management + Reduced service turnaround<br>time<br>* Technician Assignment & Scheduling + Improved record accuracy<br>influences<br>customer satisfaction<br>* Spare Parts Inventory Control + Higher<br>+Automated Invoicing i| « Better spare parts availabilty<br>i<br>+ Customer Feedback Capture i| + Rellable management reporting<br>i<br>|<br>+ Management Reporting H<br>|<br>i<br>|<br>i<br>1<br>1<br>i<br>|<br>1<br>i<br>MODERATING / INTERVENING VARIABLES<br>Staff ICT literacy + Management support + Infrastructure reliability + Organizational policy<br><!-- End of picture text -->

_Figure 2.1: Conceptual framework showing the relationship between the After-Sales Service Information System and service delivery quality_ 

14 

# **CHAPTER THREE: METHODOLOGY** 

## **3.0 Introduction** 

This chapter presents the methodology adopted in the development of the proposed AfterSales Service Information System. It describes the system development model employed, the methods used to gather system requirements, an analysis of the existing manual after-sales service process and its associated limitations, and an overview of the proposed system together with the anticipated benefits arising from its implementation. 

## **3.1 System Development Model** 

The development of the proposed After-Sales Service Information System adopted the **Rapid Application Development (RAD)** model. RAD is an iterative software development methodology that emphasizes rapid prototyping, continuous user involvement, and incremental refinement of system requirements throughout the development process (Martin, 1991) 

The selection of the RAD model was considered appropriate because the operational requirements of the organization evolved progressively during requirements elicitation. Continuous interaction with front-desk staff, technicians, and management facilitated the identification and refinement of system requirements that could not be fully established during the initial stages of the study. The iterative nature of RAD therefore provided an effective mechanism for incorporating user feedback into successive system prototypes before implementation. 

The methodology comprised iterative phases of requirements gathering, prototype development, user evaluation, and system refinement. Feedback obtained from users during each iteration informed improvements to the proposed system, thereby ensuring that the final design adequately reflected the operational workflow of the organization. 

The RAD model was considered more suitable than the traditional Waterfall model because it accommodates evolving requirements throughout the development process. Furthermore, it provides greater flexibility than sequential development approaches while remaining appropriate for the project's academic timeframe and single-developer implementation. 

15 



<!-- Start of picture text -->
—<br>v—<br>v—<br>: A<br>1, REQUIREMENTS,PLANNING a)<br>6 =<br>4, CUTOVER<br>3. CONSTRUCTION * Testing<br>* Data Conversion<br>© Training<br>* Deployment<br>* Maintenance<br><!-- End of picture text -->

_Figure 3.1: Rapid Application Development (RAD) Model_ 

## **3.2 Requirements Gathering** 

System requirements were gathered to establish the operational and functional needs of the proposed After-Sales Service Information System. Multiple data collection methods were employed to ensure that the requirements accurately reflected existing organizational processes while providing sufficient information for the design of the proposed system. The methods adopted included interviews, observation, and document review. 

### **3.2.1 Interviews** 

Semi-structured interviews were conducted with front-desk staff, technicians, and the service manager. The interviews sought to obtain information regarding the existing after-sales service procedures, operational challenges encountered during service delivery, and user expectations for the proposed computerized system. 

The interview findings provided valuable insights into the responsibilities of different categories of users, the sequence of activities performed during service delivery, and the information required to support effective management of customer service requests. 

16 

### **3.2.2 Observation** 

Direct observation was undertaken to obtain an accurate understanding of the existing aftersales service workflow. The observation focused on customer registration, service request logging, technician assignment, spare parts management, invoice preparation, and customer feedback collection. 

Observation enabled the identification of manual procedures that were not adequately documented and facilitated verification of information obtained through interviews. 

### **3.2.3 Document Review** 

Existing organizational documents were examined to identify the information currently captured during after-sales service operations. These documents included service request logbooks, stock cards, customer records, invoices, and warranty records. 

The review assisted in identifying the data elements required by the proposed system and informed the design of the database structure presented in the subsequent chapters. 

## **3.3 Analysis of the Current System** 

The organization currently manages after-sales service activities using predominantly manual procedures. Customer information and service requests are recorded in handwritten logbooks, technician assignments are communicated verbally or through telephone calls, spare parts are monitored using stock cards, and invoices are prepared manually upon completion of repairs. 

Although these procedures have supported organizational operations over time, the continued growth in customer numbers and service requests has significantly increased the complexity of managing after-sales activities. The absence of an integrated information system has resulted in fragmented record management, delayed information retrieval, and limited visibility of service operations. 

Furthermore, the current approach provides limited support for monitoring technician workload, tracking service request progress, managing spare parts inventory, and generating management reports required for operational decision-making. 

17 

## **3.4 Limitations of the Current System** 

Analysis of the existing system identified several operational limitations that negatively affect service delivery. These include: 

- Manual recording of customer information and service requests, resulting in delayed retrieval of records and an increased risk of data loss. 

- Limited visibility of service request progress, making it difficult for staff to provide customers with accurate updates regarding repair status. 

- Inefficient technician assignment resulting from the absence of a centralized scheduling mechanism. 

- Manual management of spare parts inventory, leading to inaccurate stock records and unexpected shortages of critical components. 

- Manual preparation of invoices, increasing the likelihood of calculation errors and inconsistencies in billing. 

- Limited reporting capabilities, restricting management's ability to monitor technician performance, service turnaround time, inventory utilization, and customer satisfaction. 

- Lack of centralized storage for customer feedback, making it difficult to evaluate service quality and identify areas requiring improvement. 

These limitations demonstrate the need for a computerized information system capable of integrating the organization's after-sales service activities within a single platform. 

## **3.5 Proposed System** 

The proposed After-Sales Service Information System is intended to automate and integrate the organization's after-sales service operations. The system will provide a centralized platform for managing customer information, product registration, service requests, technician assignments, spare parts inventory, invoicing, and customer feedback. 

The proposed system will enable authorized users to register customers, record service requests, assign technicians, monitor repair progress, update inventory records automatically following spare parts usage, generate invoices, and produce management reports. In addition, the system will facilitate the collection and management of customer feedback, thereby supporting continuous improvement of service delivery. 

18 

The implementation of a centralized database will improve data consistency, reduce duplication of records, and enhance the accessibility of operational information throughout the organization. 

## **3.6 Expected Benefits of the Proposed System** 

Implementation of the proposed After-Sales Service Information System is expected to improve both operational efficiency and service quality. Specifically, the proposed system is expected to: 

- Improve the accuracy and accessibility of customer and service records. 

- Reduce the time required to register and process service requests. 

- Enhance technician assignment and workload management. 

- Improve spare parts inventory control through automated stock monitoring. 

- Automate invoice generation and improve billing accuracy. 

- Support informed managerial decision-making through comprehensive operational reports. 

- Improve customer satisfaction by facilitating timely communication and efficient service delivery. 

- Strengthen organizational accountability through centralized management of service activities. 

The proposed system therefore addresses the operational challenges identified during the analysis of the existing manual system while providing a scalable platform capable of supporting future organizational growth. 

19 

# **CHAPTER FOUR: SYSTEM ANALYSIS** 

## **4.0 Introduction** 

This chapter presents the analysis of the proposed After-Sales Service Information System. It identifies the functional and non-functional requirements that the proposed system is expected to satisfy based on the findings obtained during the requirements gathering process described in Chapter Three. The chapter further presents the logical models used to analyse the proposed system, including the Data Flow Diagrams (DFDs) and the Entity Relationship Diagram (ERD). These models provide a logical representation of the proposed system before implementation and serve as the foundation for the system design presented in Chapter Five. 

## **4.1 System Requirements** 

System requirements specify the capabilities and quality attributes that the proposed AfterSales Service Information System must possess in order to meet user requirements and organizational objectives. The requirements are categorized into functional requirements, which describe the services the system will provide, and non-functional requirements, which define the quality characteristics expected of the proposed system. 

### **4.1.1 Functional Requirements** 

The proposed After-Sales Service Information System shall provide the following functionalities: 

- The proposed system shall allow authorized users to register and manage customer records. 

- The proposed system shall enable staff to register products and associate them with individual customers. 

- The proposed system shall allow users to log new service requests by recording customer information, product details, fault descriptions, and service priorities. 

- The proposed system shall enable supervisors to assign service requests to technicians based on specialization and availability. 

20 

- The proposed system shall allow technicians to update the status of service requests throughout the repair process. 

- The proposed system shall maintain a spare parts inventory and automatically update stock quantities whenever parts are issued for repairs. 

- The proposed system shall generate notifications when spare parts inventory falls below predefined reorder levels. 

- The proposed system shall generate invoices for completed service requests based on labour charges and spare parts utilized. 

- The proposed system shall enable customers to provide feedback upon completion of service requests. 

- The proposed system shall generate reports relating to customer service requests, technician workload, spare parts utilization, and customer feedback. 

- The proposed system shall enforce role-based access control to ensure that users access only functions authorized for their respective roles. 

### **4.1.2 Non-Functional Requirements** 

The proposed After-Sales Service Information System shall satisfy the following nonfunctional requirements. 

#### **_Usability_** 

The proposed system shall provide a user-friendly interface that enables authorized users to perform routine operations efficiently with minimal training. 

#### **_Performance_** 

The proposed system shall process customer registration, service request management, inventory updates, and report generation within acceptable response times under normal operating conditions. 

#### **_Reliability_** 

The proposed system shall ensure the integrity, consistency, and availability of organizational data through appropriate database management and backup mechanisms. 

21 

#### **_Security_** 

The proposed system shall require user authentication before access is granted and shall implement role-based authorization to protect sensitive organizational information from unauthorized access. 

#### **_Scalability_** 

The proposed system shall accommodate future growth in customer records, products, technicians, spare parts, and service requests without requiring significant modification to the database structure. 

#### **_Maintainability_** 

The proposed system shall employ a modular design to facilitate future maintenance, upgrades, and the integration of additional functionalities. 

## **4.2 Data Flow Diagrams** 

Data Flow Diagrams (DFDs) are graphical models used to illustrate the movement of information within an information system. They describe how data enters the system, the processes through which it passes, where it is stored, and how it is communicated to external entities. The proposed After-Sales Service Information System is represented using a Context Diagram and a Level One Data Flow Diagram. 

### **4.2.1 Context Diagram** 

The Context Diagram presents the proposed After-Sales Service Information System as a single process interacting with external entities. The principal external entities include the Customer, Technician, Manager, and Supplier. 

Customers will submit service requests and receive service updates, invoices, and acknowledgements of customer feedback. Technicians will receive assigned service requests and update repair progress. Managers will access reports and administer system operations, while suppliers will provide spare parts required for repair activities. 

22 



<!-- Start of picture text -->
es,<br>= 6 io se MANAGER<br>CUSTOMER:r og% erp toONwhey /ADMINISTRATOR<br>Sen 0een May, Wo ox or4 ov<br>Fee Rey pCail grwis&<br>Nba 3 vs<br>; oe (ss.<br>iS &<br>os. SY<br>SF SS<br>0 ves<br>ARTER-SALES<br>SERVICE<br>ead oa INFORMATION<br>op SYSTEM<br>peoP potco ReonPurch Orde,<br>et! der Roo<br>ow Ques<br>TECHNICIAN a StooyDey,Vere SUPPLIER<br>Repair St 4 Sek Ing Pe<br>ServicePatt5 UsedReport rm;‘Aion<br><!-- End of picture text -->

_Figure 4.1: Context Diagram (Level 0)_ 

### **4.2.2 Level One Data Flow Diagram** 

The Level One Data Flow Diagram provides a more detailed representation of the proposed system by decomposing it into its principal functional processes. These processes include customer registration, product registration, service request management, technician assignment, spare parts management, invoice generation, customer feedback management, and report generation. 

The diagram illustrates the movement of information between these processes, the associated data stores, and the external entities interacting with the system. It demonstrates how information will flow through the proposed system to support efficient management of aftersales service operations. 

23 



<!-- Start of picture text -->
era Detsls ———> aay Customer information ——____.<br>Castomer & DI CUSTOMER FILE<br>=a Product Information ———<br>ba D2 PRODUCT FILE<br>Customer<br>/ Product<br>Record<br>Service Request 20 Customer Record<br>customer | (Fault Description) , ae<br>ee<br>Service Request Service Request Details D3. SERVICE REQUEST FILE<br>Technician3.0 \ 4 TechnicianInformation Dy TeCHNICHANFILE<br>Assigned<br>seamen ple I ei call<br>(Gervice Details)Job og ON<br>Assignment Details<br>Technician Updates/ e Assigned Request Up tk<br>ana<br>fo<br>ADMINISTRATOR Repair:Pits peelee Superee aDelivered Parts/<br>Service Report Cound Serves Stock Information<br>aecy Invoice Details ———D6 INVOICEFILE —---~--Invoice<br>ea<br>ce ala<br>Processing (Bill) -----<br>Feedback / Rating / * Service Completion<br>Comments . Ceca ——<br>Castomer D7 FEEDBACK FILE<br>Feedback<br>/ 70 Feedback Data<br>MANAGER Report Request<br>ADMINISTRATOR Management Reports / ;<br>Performance Reports / procs,<br>Inventory Reports /<br>Feedback Reports<br><!-- End of picture text -->

_Figure 4.2: Level One Data Flow Diagram_ 

## **4.3 Entity Relationship Diagram (ERD)** 

The Entity Relationship Diagram (ERD) provides the logical representation of the database required to support the proposed After-Sales Service Information System. It identifies the principal entities, their attributes, primary keys, foreign keys, and the relationships among them. The ERD serves as the blueprint for the database that will be designed during the system design phase. 

The proposed system comprises entities representing customers, products, warranties, technicians, service requests, service records, spare parts, invoices, customer feedback, and the spare parts utilized during repairs. The relationships among these entities ensure that information is stored efficiently while minimizing redundancy and maintaining data integrity. 

24 



<!-- Start of picture text -->
1:M<br>CUSTOMER PRODUCT ix, WARRANTY<br>2<br>PK CustomerID PK ProductlD PK WarrantyLD<br>fullName FK CustomerID FK ProductID<br>phoneNumber ProductName StartDate<br>Email Model EndDate<br>PhysicalAddress SerialNumber Terms<br>dateRegistered PurchaseDate<br>dp<br>1:1<br>INVOICE<br>PK InvoieeID SERVICE_REQUEST SERVICE_RECORD<br>FK RequestID<br>AmountDue<br>PaymentStatus PK RequestID PK RecordlD<br>Datelssued FK CustomerID ‘ FK RequestID.<br>FK ProductlD 2S ActionsTaken<br>FK TeehnicianlD CompletionDate<br>DateLogged LabourCost<br>FEEDBACK IssueDescription<br>i Priority<br>Status =<br>PK FeedbackID Es<br>FK RequestID<br>Rating -<br>Comments fe<br>dbackDate =<br>; PARTS_USED<br>TECHNICIAN 7 -<br>1M<br>PK TechnicianID SPARE_PART PKTK ReconPartsUsedID<br>FullName FK PartlD<br>PhoneSpecialization PK- PartD QuanityiantityUsed<br>Availability PartName_<br>QuantitylnStock<br>ReorderLevel<br>UnitPrice<br><!-- End of picture text -->

_Figure 4.3: Entity Relationship Diagram_ 

The ERD illustrates relationships such as one customer owning multiple products, one technician handling multiple service requests, and one service request generating a corresponding service record and invoice. The many-to-many relationship between service records and spare parts is resolved through the associative entity **PARTS_USED** , thereby ensuring an efficient relational database structure. 

25 

# **CHAPTER FIVE: SYSTEM DESIGN** 

## **5.0 Introduction** 

This chapter presents the design of the proposed After-Sales Service Information System based on the analysis presented in Chapter Four. It describes the architectural structure of the proposed system, the database design, and the user interface design that will guide system implementation. The design aims to ensure that the proposed system satisfies the identified functional and non-functional requirements while maintaining scalability, maintainability, and ease of use. 

## **5.1 System Architecture** 

System architecture defines the overall structure of the proposed system by illustrating how the various software components interact to process user requests and manage organizational data. The proposed After-Sales Service Information System will adopt a three-tier architecture comprising the Presentation Layer, Application Layer, and Data Layer. This architectural approach promotes modularity, simplifies maintenance, and facilitates future system enhancement. 



<!-- Start of picture text -->
5<br>§<br>Es28sF RECEPTIONCustomer/ FRONT& service DESK TECHNICIANJob statusWORKSTATION& parts MANAGERReportsDASHBOARD& system<br>: request entry updates administration<br>§<br>a5 APPLICATION / BUSINESS LOGIC TIER<br>ig Authentication + Service Request Workflow + Technician Assignment<br>gz Spare Parts & Stock Control » Invoice Calculation * Reporting Engine<br>gs DATABASE SERVER<br>ae<br>ar Customers + Products + Warranties + Technicians<br>Service Requests Spare Parts + Invoices » Feedback<br><!-- End of picture text -->

_Figure 5.1: Three-Tier System Architecture_ 

26 

### **Presentation Layer** 

The Presentation Layer will provide the graphical user interface through which users interact with the system. It **will be developed using HTML5, CSS3, JavaScript, and Bootstrap** to create responsive and user-friendly interfaces. The layer will provide interfaces for customer registration, product registration, service request logging, technician assignment, spare parts management, invoice generation, customer feedback, and report viewing. 

### **Application Layer** 

The Application Layer will implement the business logic of the proposed system. It **will be developed using PHP** , which will process user requests, validate user input, enforce business rules, manage authentication and authorization, generate invoices, update inventory records, and facilitate communication between the presentation layer and the database. 

### **Data Layer** 

The Data Layer will manage data storage and retrieval. It **will utilize MySQL** as the database management system to store customer records, products, warranties, technicians, service requests, service records, spare parts, invoices, and customer feedback. The database will maintain data integrity through the use of primary keys, foreign keys, and referential integrity constraints. 

### **5.1.1 Proposed Development Tools** 

The proposed After-Sales Service Information System will be developed using the software tools and technologies presented in Table 5.1. These tools have been selected based on their suitability for developing database-driven web applications, ease of maintenance, availability of documentation, and compatibility with the proposed system architecture. 

|**Category**|**Proposed Tool**|**Purpose**|
|---|---|---|
|**Front-end**|HTML5, CSS3, JavaScript,<br>Bootstrap|User interface development|
|**Back-end**|PHP|Business logic and server-side<br>processing|
|**Database**|MySQL|Data storage and management|
|**Development**<br>**Environment**|XAMPP|Local web server and database server|
|**Code Editor**|Visual Studio Code|Source code development|
|**Version Control**|Git|Source code management|



27 

**Diagramming Tool** Draw.io System diagrams 

_Table 5.1 Proposed Development Tool_ 

The selected technologies are open-source, widely supported, and suitable for the development of small- to medium-scale web-based information systems. Their adoption is expected to facilitate efficient system development, simplify maintenance, and provide sufficient scalability to accommodate future enhancements. 

## **5.2 Database Design** 

The proposed system will employ a relational database designed to support efficient storage, retrieval, and management of organizational information. The database structure is derived directly from the Entity Relationship Diagram presented in Chapter Four. 

Each entity identified in the ERD will be implemented as a database table, while relationships between entities will be maintained using primary keys and foreign keys. The database design aims to eliminate redundancy, preserve data integrity, and facilitate efficient query processing. 

### **5.2.1 Database Requirements Analysis** 

The proposed After-Sales Service Information System requires a database capable of supporting all operational activities performed within the service department. The database will store customer information, product details, warranty information, technician records, service requests, service records, spare parts inventory, invoices, and customer feedback. 

The database must satisfy the following requirements: 

- Store customer and product information accurately. 

- Support the tracking of service requests throughout their lifecycle. 

- Maintain technician assignment records. 

- Manage spare parts inventory. 

- Support invoice generation and payment tracking. 

- Capture customer feedback. 

- Generate reports for management decision-making. 

- Maintain data integrity and minimize redundancy. 

28 

These requirements formed the basis for the logical and physical database designs presented in the subsequent sections. 

### **5.2.2 Logical Database Design** 

The logical database design defines the entities, attributes, and relationships required by the proposed system without considering the specific implementation details of the database management system. It is derived directly from the Entity Relationship Diagram (ERD) presented in Section 4.3. 

The logical design identifies the following principal entities: 

- CUSTOMER 

- PRODUCT 

- WARRANTY 

- TECHNICIAN 

- SERVICE_REQUEST 

- SERVICE_RECORD 

- SPARE_PART 

- PARTS_USED 

- INVOICE 

- FEEDBACK 

Relationships among these entities are established using primary and foreign keys to ensure referential integrity and efficient retrieval of related information. The logical design also resolves many-to-many relationships through associative entities such as **PARTS_USED** . 

### **5.2.3 Physical Database Design** 

The physical database design translates the logical model into relational database tables that will be implemented during system development. Each entity is represented as a table with defined fields, data types, primary keys, foreign keys, and integrity constraints. 

#### **_CUSTOMER Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**CustomerID**|INT|Primary Key, Auto<br>Increment|Unique customer<br>identifier|



29 

|**FullName**|VARCHAR(100)|NOT NULL|Customer's full name|
|---|---|---|---|
|**PhoneNumber**|VARCHAR(20)|UNIQUE, NOT NULL|Contact number|
|**Email**|VARCHAR(100)|NULL|Email address|
|**PhysicalAddress**|VARCHAR(150)|NULL|Customer address|
|**DateRegistered**|DATETIME|Default Current<br>Timestamp|Registration date|
||_Table_|_5.2 Customer Table_||



#### **_PRODUCT Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**ProductID**|INT|Primary Key|Product identifier|
|**CustomerID**|INT|Foreign Key|Owner of product|
|**ProductName**|VARCHAR(100)|NOT NULL|Product name|
|**Model**|VARCHAR(100)|NOT NULL|Product model|
|**SerialNumber**|VARCHAR(100)|UNIQUE|Serial number|
|**PurchaseDate**|DATE|NULL|Purchase date|



_Table 5.3 Product Table_ 

#### **_WARRANTY Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**WarrantyID**|INT|Primary Key|Warranty identifier|
|**ProductID**|INT|Foreign Key|Associated product|
|**StartDate**|DATE|NOT NULL|Warranty start date|
|**EndDate**|DATE|NOT NULL|Warranty expiry date|
|**Terms**|TEXT|NULL|Warranty conditions|



_Table 5.4 Warranty Table_ 

#### **_TECHNICIAN Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**TechnicianID**|INT|Primary Key|Technician identifier|
|**FullName**|VARCHAR(100)|NOT NULL|Technician name|
|**Specialization**|VARCHAR(100)|NOT NULL|Area of specialization|
|**Phone**|VARCHAR(20)|NULL|Contact number|
|**Availability**|ENUM|Default Available|Technician status|
||_Table_|_5.5 Technician Table_||



#### **_SERVICE_REQUEST Table_** 

|**Field**<br>**Data Type**|**Constraint**|**Description**|
|---|---|---|



30 

|**RequestID**|INT|Primary Key|Service request<br>identifier|
|---|---|---|---|
|**CustomerID**|INT|Foreign Key|Customer|
|**ProductID**|INT|Foreign Key|Product|
|**TechnicianID**|INT|Foreign Key|Assigned<br>technician|
|**DateLogged**|DATETIME|Default Current Timestamp|Date logged|
|**IssueDescription**|TEXT|NOT NULL|Fault description|
|**Priority**|ENUM|Low, Medium, High|Request priority|
|**Status**|ENUM|Pending, In Progress, Completed,<br>Closed|Current status|



_Table 5.6 Service Request Table_ 

#### **_SERVICE_RECORD Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**RecordID**|INT|Primary Key|Record identifier|
|**RequestID**|INT|Foreign Key|Related request|
|**ActionsTaken**|TEXT|NOT NULL|Repair performed|
|**CompletionDate**|DATE|NULL|Completion date|
|**LabourCost**|DECIMAL(10,2)|NOT NULL|Labour charges|



_Table 5.7 Service Record Table_ 

#### **_SPARE_PART Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**PartID**|INT|Primary Key|Part identifier|
|**PartName**|VARCHAR(100)|NOT NULL|Spare part|
|**QuantityInStock**|INT|NOT NULL|Available quantity|
|**ReorderLevel**|INT|Default 5|Minimum stock|
|**UnitPrice**|DECIMAL(10,2)|NOT NULL|Selling price|



_Table 5.8 Spare Part Table_ 

#### **_PARTS_USED Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**PartsUsedID**|INT|Primary Key|Record identifier|
|**RecordID**|INT|Foreign Key|Service record|
|**PartID**|INT|Foreign Key|Spare part|
|**QuantityUsed**|INT|NOT NULL|Quantity consumed|



_Table 5.9 Part Used Table_ 

31 

#### **_INVOICE Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**InvoiceID**|INT|Primary Key|Invoice identifier|
|**RequestID**|INT|Foreign Key|Service request|
|**AmountDue**|DECIMAL(10,2)|NOT NULL|Total charges|
|**PaymentStatus**|ENUM|Unpaid, Paid, Partially Paid|Payment status|
|**DateIssued**|DATETIME|Default Current Timestamp|Invoice date|



_Table 5.10 Invoice Table_ 

#### **_FEEDBACK Table_** 

|**Field**|**Data Type**|**Constraint**|**Description**|
|---|---|---|---|
|**FeedbackID**|INT|Primary Key|Feedback identifier|
|**RequestID**|INT|Foreign Key|Service request|
|**Rating**|INT|CHECK (1–5)|Customer rating|
|**Comments**|TEXT|NULL|Customer comments|
|**FeedbackDate**|DATETIME|Default Current Timestamp|Submission date|



_Table 5.11 Feedback Table_ 

## **5.3 Input Design** 

Input design specifies the manner in which users will interact with the proposed system to capture, validate, and submit data. The objective of the input design is to ensure that information is entered accurately, consistently, and efficiently while minimizing user errors. The proposed system will provide graphical user interfaces that are simple, intuitive, and consistent across all modules. 

The proposed user interfaces will include: 

- Login Interface 

- Customer Registration Interface 

- Product Registration Interface 

- Service Request Logging Interface 

- Technician Assignment Interface 

- Spare Parts Management Interface 

- Invoice Generation Interface 

- Customer Feedback Interface 

32 



<!-- Start of picture text -->
poet<br>Welcome Back<br>Username<br>ee Please sign in to your account<br>Enter your username<br>Password<br>After-Salesj Serviceé @ Enter your password o |<br>Information System Remember me Forgot password?<br>SreeimprovingNCI aiereaains customer Seto satisfaction. woe oa<br>Login as Guest<br>Don't have an account? Contact Administrator<br><!-- End of picture text -->

#### _Figure 5.2: Login Interface_ 



<!-- Start of picture text -->
eeInformation- System = =é ‘Admin User<br>Customers > Customer Registration<br>f¥_ Dashboard<br>customers CustomerEnter customer details Registrationto registera new customer<br>& Add New Customer Personal Information<br>G&i Customer<br>fodB_ servicelacied RequestsList FirstEmailEnter Name Address first name LastEnter Name* last name National ID / Passport No.PhoneEnter Number phone number*<br>a ‘Assigned. Jobs Enter email address Enter ID or passport number<br>INveNTORY dasa<br>@ Spare Parts Enterfull address<br>@ Stockin<br>@ Stock Out pescational informationpos<br>BILLING Date of Registration * Preferred Contact Method<br>B invoices edimmiyyry C3 | | setectoption .<br>a Notes<br>H_ Payments<br>REPORTS Enter any additional notes (optioned) 4<br>tht Reports<br>‘Account Information<br>sertines Username * Password*<br>Ate Enter username Enter password eo<br>BE System Settings<br>E> Logout<br>© 2026 After Seles Service Information System<br><!-- End of picture text -->

_Figure 5.3: Customer Registration Interface_ 

33 



<!-- Start of picture text -->
After-Sales Service = — 2<br>Information System = am Admin User ¥<br>f% Dashboard Services > Service Requests > New Request<br>New Service Request 7<br>2 Customers<br>#8 Customer List Cuntomer*4. Customer information Phone Number mai<br>mai Select customer v Auto-filled Auto-filled:<br>Bi Service Requests<br>I Assigned Jobs 2. Product Information<br>© Service History Product * Mode! ‘Serial Number<br>Sect product v] [tae Enter sriat number<br>INVENTORY<br>6 Spare Parts 3. Service Details<br>oO Stock n Request Type * Priority * Preferred Date<br>Oeeen Select request ype v | [ setet erty v | [ earmmaryy o<br>BLING Problem Description *<br>B invoices Describethe issue in detail<br>© Payments 4<br>Pe 4, Additional information<br>th Reports Lecaton / Address Aatachment (Options)<br>sernnes25 veers Notes (Options<br>system Settings<br>{cancer<br>[> Loge Saveas Draft Submit Request<br>(© 2026 Aer les Service nfcrmation Sytem<br><!-- End of picture text -->

_Figure 5.4: Service Request Interface_ 

## **5.4 Output Design** 

Output design describes the information that will be generated by the proposed After-Sales Service Information System. These outputs are intended to support operational activities, management decision-making, and customer service delivery. The system will present information on-screen and provide options for printing or exporting reports where necessary. 

The proposed system will generate the following outputs: 

- Customer Service Status Reports 

- Technician Workload Reports 

- Spare Parts Inventory Reports 

- Low Stock Notifications 

- Service Invoices 

- Customer Feedback Reports 

34 

- Service Performance Reports 

- Management Summary Reports 

To illustrate the proposed output design, a sample wireframe of the **Service Request Status Report** is presented below. 



<!-- Start of picture text -->
SERVICE REQUEST STATUS REPORT<br>Report Period: Date:<br>sRoo4 Grace Wanjiku 15/03/2026 Completed<br>SUMMARY<br>Total Requests: Completed: Pending: In Progress:<br>5 2 1 2<br>Generated By: Signature:<br><!-- End of picture text -->

_Figure 5.5: Low-Fidelity Wireframe of the Service Request Status Report_ 

The remaining outputs will follow a similar design approach, using structured tables and summary sections to ensure that information is presented in a clear, accurate, and userfriendly manner. Reports will be accessible through the system interface and may be printed or exported for organizational use. 

## **5.5 Summary** 

This proposal has presented the case for, and the design of, an After-Sales Service Information System intended to replace the manual, paper-based processes currently used to manage customer service requests, technician assignments, spare parts inventory, invoicing, and customer feedback. Chapter One established the problem motivating this study: a service department that has outgrown the logbooks, verbal communication, and handwritten invoices it depends on, resulting in lost records, scheduling conflicts, undetected stock shortages, and 

35 

an absence of reliable management reporting. Chapter Two situated this problem within the wider literature on after-sales service and information systems, confirming that while the strategic importance of after-sales service and the general principles of systems design are well established, comparatively little documented work addresses how a small or mediumsized service business can adopt an integrated, purpose-built system without the cost and complexity of an enterprise CRM platform — the specific gap this project responds to. 

Chapter Three set out the Rapid Application Development methodology adopted for the project, together with the requirements-gathering methods used to understand the current manual process, its limitations, and the benefits expected from the proposed system. Chapter Four translated those requirements into a formal system analysis, specifying the functional and non-functional requirements the system must satisfy and modelling the proposed system's structure and data flows through the Context Diagram, Level One Data Flow Diagram, and Entity Relationship Diagram. Chapter Five then carried that analysis into a concrete design, presenting the three-tier system architecture, the physical database schema derived from the ERD, and the proposed input and output interfaces intended to guide development. 

Taken together, the preceding chapters demonstrate that the proposed system is grounded in a genuine, well-documented organizational problem, supported by relevant literature, and translated into a coherent and implementable design. The system, once developed, is expected to address the operational inefficiencies identified in Chapter One directly: centralizing service records, enforcing structured technician assignment, automating spare parts tracking, reducing invoicing errors, and providing management with reliable operational reports. The next phase of this project will involve the implementation and testing of the system as designed, which falls outside the scope of the present proposal. 

36 

## **REFERENCES** 

- Alshourah, S., Al-Hawary, S. I., & Alolayyan, M. N. (2024). The impact of customer relationship management on customer satisfaction and loyalty: Evidence from service organizations. _Journal of Open Innovation: Technology, Market, and Complexity_ , 100215. 

- Connolly, T., & Begg, C. (2014). _Database Systems: A Practical Approach to Design, Implementation, and Management._ Harlow: Pearson Education. 

- Kendall, K. E., & Kendall, J. E. (2014). _Systems Analysis and Design._ Boston: Pearson Education. 

- Kotler, P., & Keller, K. L. (2016). _Marketing Management._ Harlow: Pearson Education. 

- Lovelock, C., & Wirtz, J. (2016). _Services Marketing: People, Technology, Strategy._ Singapore: World Scientific Publishing. 

- Martin, J. (1991). _Rapid Application Development._ New York: Macmillan Publishing Company. 

- Pressman, R. S., & Maxim, B. R. (2020). _Software Engineering: A Practitioner's Approach._ New York: McGraw-Hill Education. 

- Silberschatz, A., Korth, H. F., & Sudarshan, S. (2019). _Database System Concepts._ New York: McGraw-Hill Education. 

- Sommerville, I. (2015). _Software Engineering._ Boston: Pearson Education. 

37 

