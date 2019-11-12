import React, {useEffect, useState} from 'react'
import tw from "tailwind.macro";
import styled from 'styled-components/macro'

const FormText = styled.span`${tw`mb-1 block text-sm`} ${props=>props.help ? tw`italic` : null} ${props=>props.error ? tw`text-red-500`: null}`
export default FormText