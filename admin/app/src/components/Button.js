import styled from "styled-components/macro";
import tw from "tailwind.macro";

const Button = styled.button`${tw`bg-gray-300 text-black border-transparent border-1 px-3 py-2 cursor-pointer inline-block`}
${props => props.danger ? tw`bg-red-200 text-red-700 border-red-500` : null}
${props => props.primary ? tw`bg-blue-200 text-blue-700 border-blue-500` : null}
`
export default Button