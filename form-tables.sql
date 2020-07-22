/****** Object:  Table [dbo].[formHead]    Script Date: 7/22/2020 10:02:47 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[formHead](
	[idIdentity] [int] IDENTITY(1,1) NOT NULL,
	[idForm] [int] NULL,
	[formName] [varchar](500) NULL,
	[formAction] [varchar](1000) NULL,
	[companyId] [int] NULL,
	[formMethod] [varchar](4) NULL,
	[datasetSQL] [varchar](7999) NULL,
	[addUser] [int] NULL,
	[addDate] [datetime] NULL,
	[updateUser] [int] NULL,
	[updateDate] [datetime] NULL,
	[flags] [int] NULL,
	[post_submit_callback] [varchar](7999) NULL,
	[post_thankyou_source] [varchar](7999) NULL,
	[emailSubject] [varchar](1000) NULL,
	[idClickThru] [int] NULL,
	[insertUser] [int] NULL,
	[insertDate] [datetime] NULL,
	[tablename] [varchar](200) NULL,
	[replaceBuiltinCallback] [bit] NULL,
	[formDirection] [varchar](100) NULL
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT ((0)) FOR [flags]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT ((6651)) FOR [insertUser]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT (getdate()) FOR [insertDate]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT ('') FOR [tablename]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT ((0)) FOR [replaceBuiltinCallback]
GO

ALTER TABLE [dbo].[formHead] ADD  DEFAULT ('') FOR [formDirection]
GO


USE [Applications]
GO

/****** Object:  Table [dbo].[formFieldGroups]    Script Date: 7/22/2020 10:03:16 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[formFieldGroups](
	[idIdentity] [int] IDENTITY(1,1) NOT NULL,
	[idGroup] [int] NULL,
	[groupName] [varchar](1000) NULL,
	[addDate] [datetime] NULL,
	[addUser] [int] NULL,
	[updateDate] [datetime] NULL,
	[updateUser] [int] NULL,
	[idForm] [int] NULL,
	[displayOrder] [int] NULL,
	[activeDate] [datetime] NULL,
	[idParentGroup] [int] NULL,
	[flags] [int] NULL,
UNIQUE NONCLUSTERED 
(
	[idGroup] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT (getdate()) FOR [addDate]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT ((136)) FOR [addUser]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT ((0)) FOR [displayOrder]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT (getdate()) FOR [activeDate]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT ((0)) FOR [idParentGroup]
GO

ALTER TABLE [dbo].[formFieldGroups] ADD  DEFAULT ((0)) FOR [flags]
GO


USE [Applications]
GO

/****** Object:  Table [dbo].[formFields]    Script Date: 7/22/2020 10:03:31 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[formFields](
	[idFormField] [int] IDENTITY(1,1) NOT NULL,
	[idForm] [int] NULL,
	[caption] [varchar](100) NULL,
	[fieldName] [varchar](100) NULL,
	[iSize] [int] NULL,
	[iMaxlength] [int] NULL,
	[widthPerc] [int] NULL,
	[widthPx] [int] NULL,
	[bRequired] [tinyint] NULL,
	[validateAs] [varchar](10) NULL,
	[mapsTo] [varchar](100) NULL,
	[addUser] [int] NULL,
	[addDate] [datetime] NULL,
	[updateUser] [int] NULL,
	[updateDate] [datetime] NULL,
	[placeholder] [varchar](100) NULL,
	[intMinValue] [int] NULL,
	[intMaxValue] [int] NULL,
	[dateMinValue] [datetime] NULL,
	[dateMaxValue] [datetime] NULL,
	[idGroup] [int] NULL,
	[orderBy] [int] NULL,
	[controlType] [varchar](20) NULL,
	[controlSQL] [varchar](7999) NULL,
	[valuesList] [varchar](7999) NULL,
	[valuesListSQL] [varchar](7999) NULL,
	[textareaCols] [int] NULL,
	[textareaRows] [int] NULL,
	[ajaxLink] [varchar](7999) NULL,
	[onChange] [varchar](7999) NULL,
	[onClick] [varchar](7999) NULL,
	[onFocus] [varchar](7999) NULL,
	[onBlur] [varchar](7999) NULL,
	[defaultValue] [varchar](500) NULL,
	[confidential] [tinyint] NULL,
	[contentURL] [varchar](200) NULL,
	[contentLong] [varchar](7000) NULL,
	[DateStrMinValue] [varchar](50) NULL,
	[DateStrMaxValue] [varchar](50) NULL,
	[def] [varchar](50) NULL,
	[activeDate] [datetime] NULL,
	[deleted] [bit] NULL,
	[displayorder] [int] NULL,
	[flagTableName] [varchar](100) NULL
) ON [PRIMARY]
GO

ALTER TABLE [dbo].[formFields] ADD  CONSTRAINT [DF_formFields_addUser]  DEFAULT ((136)) FOR [addUser]
GO

ALTER TABLE [dbo].[formFields] ADD  CONSTRAINT [DF_formFields_addDate]  DEFAULT (getdate()) FOR [addDate]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((9999)) FOR [orderBy]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((40)) FOR [textareaCols]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((4)) FOR [textareaRows]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((0)) FOR [confidential]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ('') FOR [def]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT (getdate()) FOR [activeDate]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((0)) FOR [deleted]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ((999)) FOR [displayorder]
GO

ALTER TABLE [dbo].[formFields] ADD  DEFAULT ('') FOR [flagTableName]
GO

USE [Applications]
GO

/****** Object:  View [dbo].[vwFormBuilder]    Script Date: 7/22/2020 10:08:01 AM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE VIEW [dbo].[vwFormBuilder]
AS
SELECT        h.formName, h.formAction, h.companyId, h.formMethod, h.datasetSQL, COALESCE (f.caption, f.fieldName) AS caption, f.fieldName, f.iSize, f.iMaxlength, f.widthPerc, f.widthPx, f.bRequired, 
                         f.validateAs, f.mapsTo, f.placeholder, f.intMinValue, f.intMaxValue, f.dateMinValue, f.dateMaxValue, COALESCE (f.orderBy, 9999) AS orderBy, g.groupName, COALESCE (f.controlType, 'textbox') AS controlType, 
                         f.controlSQL, f.valuesList, f.valuesListSQL, h.idForm, f.textareaCols, f.textareaRows, f.ajaxLink, f.onChange, f.onClick, f.onBlur, f.onFocus, g.idGroup, f.idFormField, f.defaultValue, h.post_submit_callback, 
                         h.post_thankyou_source, h.emailSubject, h.idClickThru, COALESCE (f.confidential, 0) AS confidential, COALESCE (g.displayOrder, 0) AS groupDisplayOrder, COALESCE (f.orderBy, 0) AS fieldOrderBy, f.contentURL, 
                         f.contentLong, f.DateStrMinValue, f.DateStrMaxValue, f.def, g.idParentGroup, COALESCE (f.deleted, 0) AS deleted, COALESCE (f.displayorder, 999) AS displayorder, h.tablename, 
                         COALESCE (h.replaceBuiltinCallback, 0) AS replaceBuiltinCallback, COALESCE (NULLIF (h.formDirection, ''), 'customer') AS formDirection, f.flagTableName
FROM            dbo.formFieldGroups AS g RIGHT OUTER JOIN
                         dbo.formHead AS h ON g.idForm = h.idForm LEFT OUTER JOIN
                         dbo.formFields AS f ON g.idGroup = f.idGroup
WHERE        (COALESCE (f.deleted, 0) = 0)
GO
